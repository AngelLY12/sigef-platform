<?php

namespace App\Core\Application\UseCases\Payments\Student\PendingPayment;

use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Repositories\Command\Payments\PaymentRepInterface;
use App\Core\Domain\Repositories\Command\User\UserRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Core\Domain\Repositories\Stripe\StripeGatewayInterface;
use App\Core\Domain\Utils\Validators\PaymentConceptValidator;
use App\Core\Domain\Utils\Validators\PaymentValidator;
use App\Exceptions\NotAllowed\PaymentRetryNotAllowedException;
use App\Exceptions\NotFound\ConceptNotFoundException;
use Illuminate\Support\Facades\DB;

class PayConceptUseCase
{
    public function __construct(
        private PaymentConceptQueryRepInterface $pcqRepo,
        private PaymentRepInterface $paymentRepo,
        private PaymentQueryRepInterface $paymentQueryRep,
        private UserRepInterface $userRep,
        private StripeGatewayInterface $stripe,
    ) {}
    public function execute(User $user, int $conceptId): string {
        $concept = $this->pcqRepo->findById($conceptId);
        if (!$concept) throw new ConceptNotFoundException();
        PaymentConceptValidator::ensureConceptIsActiveAndValid($concept, $user);

        $customerId = $this->verifyStripeCustomer($user);

        $lastPayment = $this->paymentQueryRep->getLastPaymentForConcept(
            $user->id,
            $conceptId,
            allowedStatuses: PaymentStatus::nonTerminalStatuses()
        );

        $amountToPay = $concept->amount;
        if ($lastPayment && $lastPayment->isUnderPaid()) {
            $amountToPay = $lastPayment->getPendingAmount();
        }
        if($lastPayment && $lastPayment->isNonPaid())
        {
            PaymentValidator::ensurePaymentIsValidToRepay($lastPayment);
            if(!$this->stripe->expireSessionIfPending($lastPayment->stripe_session_id))
            {
                throw new PaymentRetryNotAllowedException('El reintento de pago no es válido, espera a que expire la sesión anterior o realiza el pago con la sesión actual.');
            }
        }

        $payment = DB::transaction(function () use ($lastPayment, $concept, $user, $amountToPay){
            if($lastPayment){
                return $lastPayment;
            }
            return $this->paymentRepo->create(
                new Payment(
                    concept_name: $concept->concept_name,
                    amount: $amountToPay,
                    status: PaymentStatus::DEFAULT,
                    user_id: $user->id,
                    payment_concept_id: $concept->id,
                )
            );
        });

        $session = $this->stripe->createCheckoutSession($customerId, $concept, $amountToPay, $user->id, $payment->id);
        $this->paymentRepo->update($payment->id, [
            'stripe_session_id' => $session->id,
            'url' => $session->url,
        ]);
        return $session->url;
    }

    private function verifyStripeCustomer(User $user): string
    {
        $customerId= $user->stripe_customer_id;
        if(!$customerId)
        {
            $createdCustomerId=$this->stripe->createStripeUser($user);
            $this->userRep->update($user->id, ['stripe_customer_id' => $createdCustomerId]);
            $customerId=$createdCustomerId;
        }
        return $customerId;
    }
}
