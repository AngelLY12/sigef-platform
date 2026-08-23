<?php

namespace App\Core\Application\UseCases\Payments\Stripe;

use App\Core\Application\Factories\Emails\Events\EmailEventFactory;
use App\Core\Application\Factories\Payments\Stripe\WebhookPaymentIntentEventFactory;
use App\Core\Application\Factories\Payments\Stripe\WebhookSessionEventFactory;
use App\Core\Application\Mappers\MailMapper;
use App\Core\Application\Services\Events\Contracts\EmailEventManagerInterface;
use App\Core\Application\Services\Events\Contracts\PaymentEventManagerInterface;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Repositories\Command\Payments\PaymentRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Repositories\Stripe\StripeGatewayInterface;
use App\Exceptions\DomainException;
use App\Jobs\ClearStudentCacheJob;
use App\Mail\PaymentFailedMail;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;

class HandleFailedOrExpiredPaymentUseCase
{
    public function __construct(
        private UserQueryRepInterface $uqRepo,
        private PaymentRepInterface $paymentRepo,
        private PaymentQueryRepInterface $pqRepo,
        private StripeGatewayInterface $stripeGateway,
        private PaymentEventManagerInterface $paymentEventManager,
        private EmailEventManagerInterface $emailEventManager,
    ) {

    }
    public function execute(Session|PaymentIntent $obj, string $eventType, string $eventId)
    {
        $paymentEventType = $this->mapEventTypeToEnum($eventType);
        $payment = $this->pqRepo->findById($obj->metadata->payment_id);
        if (!$payment) {
            return false;
        }

        $event = $this->paymentEventManager->findOrCreate(
            stripeEventId: $eventId,
            eventType: $paymentEventType,
            factory: fn () => $this->createPaymentEvent(
                payment: $payment,
                obj: $obj,
                eventId: $eventId,
                eventType: $paymentEventType,
            )
        );

        if ($event->processed) {
            return true;
        }

        try {
            $this->paymentEventManager->process(
                event: $event,
                callback: fn () => $this->processFailedPayment(
                    event: $event,
                    payment: $payment,
                    obj: $obj,
                    eventType: $paymentEventType,
                    eventId: $eventId,
                )
            );
            return true;
        }catch (\Exception $e) {
            if (!($e instanceof DomainException) && !($e instanceof \Illuminate\Validation\ValidationException)) {
                throw $e;
            }
            return false;
        }
    }
    private function processFailedPayment(
        PaymentEvent $event,
        Payment $payment,
        Session|PaymentIntent $obj,
        PaymentEventType $eventType,
        string $eventId,
    ): void {
        $user = $this->uqRepo->getUserByStripeCustomer($obj->customer);

        $error = $this->determineErrorMessage(
            obj: $obj,
            eventType: $eventType,
        );

        $hasPartialPayment = ($payment->amount_received ?? 0) > 0;

        $this->sendFailedOrExpiredPaymentMail(
            user: $user,
            payment: $payment,
            error: $error,
            eventId: $eventId,
        );

        if (!$hasPartialPayment) {
            $this->paymentRepo->delete($payment->id);
        } else {
            $this->stripeGateway->expireSessionIfPending(
                $payment->stripe_session_id
            );
        }

        ClearStudentCacheJob::dispatch($user->id)
            ->onQueue('cache');

        $event->setStatus(PaymentStatus::FAILED);
    }
    private function determineErrorMessage(Session|PaymentIntent $obj, PaymentEventType $eventType): string
    {
        return match ($eventType) {
            PaymentEventType::WEBHOOK_PAYMENT_FAILED =>
                $obj->last_payment_error->message
                ?? 'El pago fue rechazado.',

            PaymentEventType::WEBHOOK_PAYMENT_CANCELLED =>
            'El pago fue cancelado.',

            PaymentEventType::WEBHOOK_SESSION_EXPIRED =>
            'La sesión de pago expiró.',

            default =>
            'Error desconocido en el pago.',
        };
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
        Payment $payment,
        Session|PaymentIntent $obj,
        string $eventId,
        PaymentEventType $eventType,
    ): PaymentEvent
    {
        return match ($eventType) {
            PaymentEventType::WEBHOOK_PAYMENT_CANCELLED =>
                WebhookPaymentIntentEventFactory::cancelled(
                    $eventId,
                    $payment,
                    $obj,
                ),
            PaymentEventType::WEBHOOK_PAYMENT_FAILED =>
                WebhookPaymentIntentEventFactory::failed(
                    $eventId,
                    $payment,
                    $obj
                ),
            PaymentEventType::WEBHOOK_SESSION_EXPIRED =>
                WebhookSessionEventFactory::expired(
                    $payment,
                    $obj,
                    $eventId
                )
        };

    }
    private function sendFailedOrExpiredPaymentMail(User $user, Payment $payment, string $error, string $eventId ): void
    {
        $emailEvent = $this->emailEventManager->findOrCreate(
            eventType: EmailEventType::PAYMENT_FAILED,
            sourceType: EmailEventSourceType::STRIPE,
            sourceId: $eventId,
            factory: fn () => EmailEventFactory::paymentFailed(
                payment: $payment,
                user: $user,
                stripeEventId: $eventId,
                errorMessage: $error,
            ),
        );

        $mail = new PaymentFailedMail(MailMapper::toPaymentFailedEmailDTO(user: $user,payment: $payment,error: $error));
        $this->emailEventManager->dispatch(
            event: $emailEvent,
            mail: $mail,
            recipientEmail: $user->email,
            jobType: 'failed_or_expired_payment',
        );
    }
}
