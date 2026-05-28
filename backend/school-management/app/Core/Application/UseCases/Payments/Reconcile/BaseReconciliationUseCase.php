<?php

namespace App\Core\Application\UseCases\Payments\Reconcile;

use App\Core\Application\DTO\Response\General\ReconciliationResult;
use App\Core\Application\Mappers\MailMapper;
use App\Core\Application\Traits\HasPaymentStripe;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Repositories\Command\Payments\PaymentRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentEventQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentMethodQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Repositories\Stripe\StripeGatewayQueryInterface;
use App\Jobs\ClearStaffCacheJob;
use App\Jobs\ClearStudentCacheJob;
use App\Jobs\SendMailJob;
use App\Mail\PaymentValidatedMail;

abstract class BaseReconciliationUseCase
{
    use HasPaymentStripe;
    public function __construct(
        protected UserQueryRepInterface $userRepo,
        protected StripeGatewayQueryInterface $stripe,
        protected PaymentRepInterface $paymentRep,
        protected PaymentQueryRepInterface $paymentQueryRep,
        protected PaymentMethodQueryRepInterface $pmRepo,
        protected PaymentEventQueryRepInterface $paymentEventRep,
    ) {
        $this->setRepository($paymentRep);
    }

    protected function processReconciliation($payment, $pi, $charge): ?object
    {
        $paymentMethodId = $charge->payment_method ?? null;

        if (!$paymentMethodId) {
            logger()->warning("Pago {$payment->id} sin payment_method en charge");
            return null;
        }

        $pm = $this->pmRepo->findByStripeId($paymentMethodId);

        if (!$pm) {
            logger()->warning("Método de pago no encontrado para pago {$payment->id}: {$paymentMethodId}");
            $pm = null;
        }

        return $this->updatePaymentWithStripeData($payment, $pi, $charge, $pm);
    }

    protected function handleSinglePaymentSideEffects(Payment $payment, ReconciliationResult $result): void
    {
        $this->dispatchCacheClearing($payment->user_id);

        $user = $this->userRepo->findById($payment->user_id);
        if ($user) {
            $this->sendSinglePaymentEmail($payment, $user);
            $result->notified++;
        }
    }

    private function dispatchCacheClearing(int $userId): void
    {
        ClearStudentCacheJob::dispatch($userId)
            ->onQueue('cache');

        ClearStaffCacheJob::dispatch()
            ->onQueue('cache');
    }

    private function sendSinglePaymentEmail(Payment $payment, User $user): void
    {
        $data = [
            'recipientName' => $user->fullName(),
            'recipientEmail' => $user->email,
            'concept_name' => $payment->concept_name,
            'amount' => $payment->amount,
            'amount_received' => $payment->amount_received,
            'payment_method_detail' => $payment->payment_method_details ?? [],
            'status' => $payment->status->value,
            'url' => $payment->url ?? null,
            'payment_intent_id' => $payment->payment_intent_id,
        ];

        $mail = new PaymentValidatedMail(
            MailMapper::toPaymentValidatedEmailDTO($data)
        );

        SendMailJob::forUser($mail, $user->email, 'single_reconcile_payment')
            ->onQueue('emails');
    }

}
