<?php

namespace App\Core\Application\Services\Payments\Staff;

use App\Core\Application\Traits\HasPaymentStripe;
use App\Core\Application\UseCases\Payments\Reconcile\ReconcilePaymentsForceUseCase;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Repositories\Command\Payments\PaymentRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentEventQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentMethodQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Repositories\Stripe\StripeGatewayQueryInterface;
use App\Core\Domain\Utils\Helpers\Money;
use App\Events\PaymentReconciledEvent;
use App\Exceptions\NotFound\ConceptNotFoundException;
use App\Exceptions\NotFound\UserNotFoundException;
use App\Exceptions\Validation\ValidationException;
use Illuminate\Support\Str;

class PaymentValidationService
{
    use HasPaymentStripe;
    public function __construct(
        private UserQueryRepInterface $uqRepo,
        private PaymentRepInterface $paymentRepo,
        private PaymentQueryRepInterface $pqRepo,
        private StripeGatewayQueryInterface $stripeRepo,
        private PaymentMethodQueryRepInterface $pmqRepo,
        private PaymentConceptQueryRepInterface $pcqRepo,
        private PaymentEventQueryRepInterface $peQueryRep,
        private ReconcilePaymentsForceUseCase $force
    ) {
        $this->setRepository($this->paymentRepo);
    }

    public function validateAndGetOrCreatePayment(
        string $search,
        string $payment_intent_id
    ): array {
        $student = $this->uqRepo->findBySearch($search);
        $existsEvent = $this->peQueryRep->existsByPaymentIntentId($payment_intent_id, PaymentEventType::SYSTEM_CORRECTION);
        if($existsEvent)
        {
            throw new ValidationException('Este pago ya fue validado antes');
        }
        if (!$student) {
            throw new UserNotFoundException();
        }

        $payment = $this->pqRepo->findByIntentOrSession($student->id, $payment_intent_id);
        $wasCreated = false;
        $wasReconciled = false;
        $reconcileResponse = null;

        if (!$payment) {
            [$payment, $wasCreated] = $this->createNewPayment($student, $payment_intent_id);
        } else {
            [$reconcileResponse, $payment, $wasReconciled] = $this->force->execute($payment);
        }

        $payment = $this->updateAmountIfNecessary($payment, $wasCreated, $wasReconciled);
        $this->createPaymentEvent($payment, $wasReconciled, $wasCreated);
        return [$payment, $student, $wasCreated, $wasReconciled ,$reconcileResponse];
    }

    private function createNewPayment(User $student, string $payment_intent_id): array
    {
        $stripe = $this->stripeRepo->getIntentAndCharge($payment_intent_id);

        $paymentConceptId = $stripe['intent']->metadata->payment_concept_id ?? null;
        $paymentConcept = $this->pcqRepo->findById($paymentConceptId);

        if (!$paymentConcept) {
            throw new ConceptNotFoundException();
        }

        $paymentMethod = null;
        $stripePaymentMethodId = $stripe['charge']->payment_method ?? null;

        if ($stripePaymentMethodId) {
            $paymentMethod = $this->pmqRepo->findByStripeId($stripePaymentMethodId);
        }


        $paymentMethodDetails = $this->formatPaymentMethodDetails(
            $stripe['charge']->payment_method_details ?? []
        );

        $expectedAmount = Money::from($paymentConcept->amount);
        $receivedAmount = Money::from($stripe['charge']->amount_received);
        $initialStatus = $this->verifyStatus($stripe['intent'], $receivedAmount, $expectedAmount);

        $payment = new Payment(
            concept_name: $paymentConcept->concept_name,
            amount: $paymentConcept->amount,
            status: $initialStatus,
            payment_method_details: $paymentMethodDetails,
            user_id: $student->id,
            payment_concept_id: $paymentConceptId,
            payment_method_id: $paymentMethod?->id,
            stripe_payment_method_id: $stripe['charge']->payment_method,
            amount_received: $stripe['charge']->amount_received,
            payment_intent_id: $payment_intent_id,
            url: $stripe['charge']->receipt_url,
            stripe_session_id: $stripe['intent']->latest_charge->id,
            created_at: now()
        );

        $payment = $this->paymentRepo->create($payment);

        return [$payment, true];
    }

    private function paymentReconcileAmount(Payment $payment): Money
    {
        $expectedAmount = Money::from($payment->amount);
        $receivedAmount = Money::from($payment->amount_received);

        if($receivedAmount->isEqualTo($expectedAmount))
        {
            return $receivedAmount;
        }elseif($receivedAmount->isLessThan($expectedAmount))
        {
            $countSessions = $this->stripeRepo->countSessionsByMetadata(
                [
                    'payment_concept_id' => $payment->payment_concept_id,
                    'user_id' => $payment->user_id,
                ],'complete'
            );
            if($countSessions <= 1)
            {
                return $receivedAmount;
            }
            $sessions = $this->stripeRepo->getSessionsByMetadata(
                [
                    'payment_concept_id' => $payment->payment_concept_id,
                    'user_id' => $payment->user_id,
                ], 'complete'
            );

            $validSessions = array_filter($sessions, fn($s) => !empty($s['amount_received']));
            if (count($validSessions) <= 1) {
                return $receivedAmount;
            }
            $totalReceived = array_sum(array_column($validSessions, 'amount_received'));
            $sessionsAmount = Money::from($totalReceived);

            return $sessionsAmount;
        }

        return $receivedAmount;

    }

    private function updateAmountIfNecessary(Payment $payment, bool $wasCreated, bool $wasReconciled): Payment
    {
        if($wasCreated || $wasReconciled)
        {
            $paymentAmount = $payment->amount_received;
            $reconcileAmount = $this->paymentReconcileAmount($payment);
            $expectedAmount = Money::from($payment->amount);
            $newStatus = $this->updateStatusIfNecessary($reconcileAmount, $expectedAmount);

            $updateData = [];
            if(!$reconcileAmount->isEqualTo($paymentAmount))
            {
                $updateData['amount_received'] = $reconcileAmount->finalize();
            }
            if ($newStatus !== $payment->status) {
                $updateData['status'] = $newStatus->value;
            }

            if (!empty($updateData)) {
                return $this->paymentRepo->update($payment->id, $updateData);
            }
        }
        return $payment;
    }

    private function updateStatusIfNecessary(Money $receivedAmount, Money $expectedAmount,): PaymentStatus
    {
        if ($receivedAmount->isLessThan($expectedAmount)) {
            return PaymentStatus::UNDERPAID;
        } elseif ($receivedAmount->isGreaterThan($expectedAmount)) {
            return PaymentStatus::OVERPAID;
        } else {
            return PaymentStatus::SUCCEEDED;
        }
    }

    private function createPaymentEvent(Payment $payment, bool $wasReconciled, bool $wasCreated):void
    {
        $eventId = 'system_correction_' . Str::uuid();
        event(new PaymentReconciledEvent(
            paymentId: $payment->id,
            eventId: $eventId,
            eventType: PaymentEventType::SYSTEM_CORRECTION->value,
            sessionId: $payment->stripe_session_id,
            outcome: 'success',
            stripeData: [
                'was_created' => $wasCreated,
                'was_reconciled' => $wasReconciled,
                'payment_intent_id' => $payment->payment_intent_id,
            ],
            previousStatus: null,
            newStatus: $payment->status->value
        ));
    }

}
