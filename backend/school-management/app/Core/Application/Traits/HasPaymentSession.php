<?php

namespace App\Core\Application\Traits;

use App\Core\Application\Factories\Emails\Events\EmailEventFactory;
use App\Core\Application\Factories\Payments\Stripe\WebhookSessionEventFactory;
use App\Core\Application\Mappers\MailMapper;
use App\Core\Application\Services\Events\Contracts\EmailEventManagerInterface;
use App\Core\Application\Services\Events\Contracts\PaymentEventManagerInterface;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Entities\PaymentMethod;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\StudentCacheSufix;
use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use App\Core\Domain\Repositories\Command\Payments\PaymentMethodRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Repositories\Stripe\StripeGatewayQueryInterface;
use App\Core\Infraestructure\Cache\CacheService;
use App\Exceptions\DomainException;
use App\Exceptions\NotFound\PaymentNotFountException;
use App\Mail\PaymentCreatedMail;
use Illuminate\Support\Facades\DB;
use Stripe\Checkout\Session;

trait HasPaymentSession
{
    private const TAG_CARDS = [CachePrefix::STUDENT->value, StudentCacheSufix::CARDS->value];
    public function __construct(
        private UserQueryRepInterface $userRepo,
        private PaymentMethodRepInterface $pmRepo,
        private StripeGatewayQueryInterface $stripe,
        private PaymentQueryRepInterface $pqRepo,
        private PaymentEventManagerInterface $paymentEventManager,
        private EmailEventManagerInterface $emailEventManager,
        private CacheService $service

    ) {

    }
    public function handlePaymentSession(Session $session, string $eventId, PaymentEventType $eventType): ?Payment
    {
        $payment = $this->pqRepo->findBySessionId($session->id);
        if (!$payment) {
            throw new PaymentNotFountException();
        }

        $user = $this->userRepo->getUserByStripeCustomer($session->customer);

        $event = $this->paymentEventManager->findOrCreate(
            stripeEventId: $eventId,
            eventType: $eventType,
            factory: fn () => $this->matchEvent(payment:$payment,
                eventType: $eventType,
                session: $session,
                eventId: $eventId,
            )
        );

        if ($event->processed) {
            return $payment;
        }

        $this->paymentEventManager->process(
            event: $event,
            callback: fn () => $this->callbackProcess(event: $event, eventType: $eventType,payment: $payment, user: $user, eventId: $eventId)
        );

        return $payment;
    }

    public function finalizeSetupSession(Session $obj)
    {
        if(empty($obj->customer))
        {
            return false;
        }
        try {
            $user = $this->userRepo->getUserByStripeCustomer($obj->customer);

            $setupIntent = $this->stripe->getSetupIntentFromSession($obj->id);
            $pm = $this->stripe->retrievePaymentMethod($setupIntent->payment_method);

            $paymentMethod = new PaymentMethod(
                user_id: $user->id,
                stripe_payment_method_id: $pm->id,
                brand: $pm->card->brand ?? null,
                last4: $pm->card->last4 ?? null,
                exp_month: $pm->card->exp_month ?? null,
                exp_year: $pm->card->exp_year ?? null
            );

            $pm= DB::transaction(function() use ($paymentMethod) {
                return $this->pmRepo->create($paymentMethod);
            });
            $this->service->flushTags(array_merge(self::TAG_CARDS, ["userId:{$user->id}"]));
            return true;
        } catch (DomainException $e) {
            return false;

        } catch (\Illuminate\Validation\ValidationException $e) {
            return false;

        } catch (\Exception $e) {
            throw $e;
        }

    }

    private function matchEvent(
        Payment $payment,
        PaymentEventType $eventType,
        Session $session,
        string $eventId,
    ): PaymentEvent
    {

        return match ($eventType) {
            PaymentEventType::WEBHOOK_SESSION_COMPLETED =>
            WebhookSessionEventFactory::completed(
                payment: $payment,
                session: $session,
                eventId: $eventId,
            ),

            PaymentEventType::WEBHOOK_SESSION_ASYNC_COMPLETED =>
            WebhookSessionEventFactory::asyncCompleted(
                payment: $payment,
                session: $session,
                eventId: $eventId,
            ),

            default => throw new \InvalidArgumentException(
                "Tipo de evento no soportado: {$eventType->value}"
            ),
        };
    }

    private function callbackProcess(PaymentEvent $event,
                                     PaymentEventType $eventType,
                                     Payment $payment,
                                     User $user,
                                     string $eventId): void
    {
        if (
            $eventType === PaymentEventType::WEBHOOK_SESSION_COMPLETED
        ) {
            $this->sendPaymentEmail(
                payment: $payment,
                user: $user,
                eventId: $eventId
            );
        }

        $event->setStatus($payment->status);
        $event->setAmountReceived($payment->amount_received ?? '0.00');
    }

    private function sendPaymentEmail(Payment $payment, User $user, string $eventId): void
    {
        $emailEvent = $this->emailEventManager->findOrCreate(
            eventType: EmailEventType::PAYMENT_CREATED,
            sourceType: EmailEventSourceType::STRIPE,
            sourceId: $eventId,
            factory: fn () => EmailEventFactory::paymentCreated(
                payment: $payment,
                user: $user,
                stripeEventId: $eventId,
            )
        );

        $data = MailMapper::toPaymentCreatedEmailDTO($payment, $user->fullName(), $user->email);
        $mail = new PaymentCreatedMail($data);
        $this->emailEventManager->dispatch(
            event: $emailEvent,
            mail: $mail,
            recipientEmail: $user->email,
            jobType: 'stripe_session',
        );
    }
}
