<?php

namespace App\Core\Application\UseCases\Payments\Stripe;

use App\Core\Application\Mappers\MailMapper;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Repositories\Command\Payments\PaymentEventRepInterface;
use App\Core\Domain\Repositories\Command\Payments\PaymentRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentEventQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Repositories\Stripe\StripeGatewayInterface;
use App\Core\Domain\Utils\Helpers\Money;
use App\Exceptions\DomainException;
use App\Jobs\ClearStudentCacheJob;
use App\Jobs\SendMailJob;
use App\Mail\PaymentFailedMail;

class HandleFailedOrExpiredPaymentUseCase
{
    public function __construct(
        private UserQueryRepInterface $uqRepo,
        private PaymentRepInterface $paymentRepo,
        private PaymentQueryRepInterface $pqRepo,
        private StripeGatewayInterface $stripeGateway,
        private PaymentEventRepInterface $paymentEventRep,
        private PaymentEventQueryRepInterface $paymentEventQueryRep
    ) {

    }
    public function execute($obj, string $eventType, string $eventId)
    {
        $paymentEventType = $this->mapEventTypeToEnum($eventType);
        $payment = $this->findPayment($obj, $eventType);
        if (!$payment) {
            logger()->warning("No se encontró el pago para el evento: {$eventType}, id: {$obj->id}");
            return false;
        }

        $event = $this->createPaymentEvent($payment, $obj, $eventId, $paymentEventType, $eventType);

        if ($event->processed) {
            logger()->info("PaymentEvent ya procesado: {$event->id} para evento {$eventType}");
            return true;
        }

        try {
            $user = $this->uqRepo->getUserByStripeCustomer($obj->customer);
            $error = $this->determineErrorMessage($obj, $eventType);
            $hasPartialPayment = $payment->amount_received > 0;
            logger()->info("Procesando pago fallido: payment_id={$payment->id}, motivo: {$error}");
            $this->sendFailedOrExpiredPaymentMail($user, $payment, $error, $eventId);
            if (!$hasPartialPayment) {
                $this->paymentRepo->delete($payment->id);
                logger()->info("Pago fallido eliminado: payment_id={$payment->id}");
            } else {
                $this->stripeGateway->expireSessionIfPending($payment->stripe_session_id);
                logger()->info("Pago parcial marcado como fallido: payment_id={$payment->id}");
            }
            ClearStudentCacheJob::dispatch($user->id)->onQueue('cache');
            $this->paymentEventRep->update($event->id, [
                'processed' => true,
                'processed_at' => now(),
                'status' => PaymentStatus::FAILED->value,
                'metadata' => array_merge($event->metadata ?? [], [
                    'email_sent' => true,
                    'payment_action' => $hasPartialPayment ? 'expired_session' : 'deleted',
                    'has_partial_payment' => $hasPartialPayment,
                    'error_message' => $error,
                    'user_id' => $user->id
                ])
            ]);
            return true;
        }catch (\Exception $e) {
            if($event->id)
            {
                $this->paymentEventRep->update($event->id, [
                    'error_message' => $e->getMessage(),
                    'retry_count' => ($event->retryCount ?? 0) + 1,
                    'metadata' => array_merge($event->metadata ?? [], [
                        'failed_at' => now()->toISOString(),
                        'error_class' => get_class($e)
                    ])
                ]);

            }
            if (!($e instanceof DomainException) && !($e instanceof \Illuminate\Validation\ValidationException)) {
                throw $e;
            }

            logger()->warning("Error procesando evento {$eventType}: " . $e->getMessage(), [
                'exception' => get_class($e),
                'event_id' => $eventId,
                'payment_id' => $payment->id ?? null
            ]);

            return false;
        }
    }

    private function findPayment($obj, string $eventType): ?object
    {
        if (in_array($eventType, ['payment_intent.payment_failed', 'payment_intent.canceled'])) {
            return $this->pqRepo->findByIntentId($obj->id);
        } elseif ($eventType === 'checkout.session.expired') {
            return $this->pqRepo->findBySessionId($obj->id);
        }

        return null;
    }

    private function determineErrorMessage($obj, string $eventType): string
    {
        if (in_array($eventType, ['payment_intent.payment_failed', 'payment_intent.canceled'])) {
            return $obj->last_payment_error->message ?? 'Error desconocido';
        } elseif ($eventType === 'checkout.session.expired') {
            return "La sesión de pago expiró";
        }

        return 'Error desconocido en el pago';
    }

    private function mapEventTypeToEnum(string $stripeEventType): PaymentEventType
    {
        return match($stripeEventType) {
            'payment_intent.payment_failed' => PaymentEventType::WEBHOOK_PAYMENT_FAILED,
            'payment_intent.canceled' => PaymentEventType::WEBHOOK_PAYMENT_CANCELLED,
            'checkout.session.expired' => PaymentEventType::WEBHOOK_SESSION_EXPIRED,
            default => PaymentEventType::WEBHOOK_PAYMENT_FAILED
        };
    }

    private function createPaymentEvent(
        $payment,
        $obj,
        string $eventId,
        PaymentEventType $eventType,
        string $stripeEventType
    ): PaymentEvent
    {
        $existing = $this->paymentEventQueryRep->findByStripeEvent($eventId, $eventType);

        if ($existing) {
            return $existing;
        }

        $amount = '0.00';
        if (isset($obj->amount) && $obj->amount > 0) {
            $amount = Money::from($obj->amount)->divide('100')->finalize();
        } elseif (isset($payment->amount_received) && $payment->amount_received > 0) {
            $amount = $payment->amount_received;
        }

        $event = PaymentEvent::createWebhookEvent(
            paymentId: $payment->id,
            stripeEventId: $eventId,
            paymentIntentId: $obj->payment_intent ?? $obj->id ?? null,
            sessionId: $obj->id ?? ($stripeEventType === 'checkout.session.expired' ? $obj->id : null),
            amount: $amount,
            eventType: $eventType,
            metadata: [
                'raw_object' => $obj,
                'stripe_event_type' => $stripeEventType,
                'email_type' => 'payment_failed_mail',
                'original_status' => $payment->status ?? null,
                'amount_original' => $payment->amount ?? null,
                'amount_received_original' => $payment->amount_received ?? null,
                'has_partial_payment' => ($payment->amount_received ?? 0) > 0,
                'error_details' => $this->determineErrorMessage($obj, $stripeEventType)
            ],
        );

        return $this->paymentEventRep->create($event);
    }

    private function sendFailedOrExpiredPaymentMail(User $user, Payment $payment, string $error, string $eventId ): void
    {
        $data = [
            'recipientName' => $user->fullName(),
            'recipientEmail' => $user->email,
            'concept_name' => $payment->concept_name,
            'amount' => $payment->amount,
            'error' => $error
        ];
        $emailEvent = $this->createEmailEvent($user, $payment, $error, $eventId);
        $mail = new PaymentFailedMail(MailMapper::toPaymentFailedEmailDTO($data));
        SendMailJob::forUser($mail, $user->email, 'failed_or_expired_payment', $emailEvent->id)->onQueue('emails');
    }

    private function createEmailEvent(User $user, Payment $payment, string $error, string $eventId): PaymentEvent
    {
        $emailEvent = PaymentEvent::createEmailEvent(
            paymentId: $payment->id,
            eventId: $eventId,
            paymentIntentId: $payment->payment_intent_id ?? null,
            sessionId: $payment->stripe_session_id ?? null,
            eventType: PaymentEventType::EMAIL_PAYMENT_FAILED,
            recipientEmail: $user->email,
            emailData: [
                'email_template' => 'payment_failed',
                'concept_name' => $payment->concept_name,
                'error_message' => $error,
                'has_partial_payment' => ($payment->amount_received ?? 0) > 0,
            ]
        );
        return $this->paymentEventRep->create($emailEvent);

    }

}
