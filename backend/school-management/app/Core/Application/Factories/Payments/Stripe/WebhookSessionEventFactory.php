<?php

namespace App\Core\Application\Factories\Payments\Stripe;

use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use App\Core\Domain\Utils\Helpers\StripeHelper;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Session\CheckoutSessionAsyncCompletedData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Session\CheckoutSessionCompletedData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Session\CheckoutSessionExpiredData;
use Stripe\Checkout\Session;

class WebhookSessionEventFactory
{
    public static function completed(
        Payment $payment,
        Session $session,
        string $eventId,
    ): PaymentEvent {
        return PaymentEvent::createWebhookEvent(
            paymentId: $payment->id,
            stripeEventId: $eventId,
            paymentIntentId: $session->payment_intent,
            sessionId: $session->id,
            amount: StripeHelper::amountFromCents($session->amount_total),
            eventType: PaymentEventType::WEBHOOK_SESSION_COMPLETED,
            metadata: CheckoutSessionCompletedData::create(metadata: $session->metadata?->toArray())
        );
    }

    public static function asyncCompleted(
        Payment $payment,
        Session $session,
        string $eventId,
    ): PaymentEvent {
        return PaymentEvent::createWebhookEvent(
            paymentId: $payment->id,
            stripeEventId: $eventId,
            paymentIntentId: $session->payment_intent,
            sessionId: $session->id,
            amount: StripeHelper::amountFromCents($session->amount_total),
            eventType: PaymentEventType::WEBHOOK_SESSION_ASYNC_COMPLETED,
            metadata: CheckoutSessionAsyncCompletedData::create(metadata: $session->metadata?->toArray())
        );
    }

    public static function expired(
        Payment $payment,
        Session $session,
        string $eventId,
    ): PaymentEvent {
        return PaymentEvent::createWebhookEvent(
            paymentId: $payment->id,
            stripeEventId: $eventId,
            paymentIntentId: $session->payment_intent,
            sessionId: $session->id,
            amount: StripeHelper::amountFromCents(
                $session->amount_total ?? 0
            ),
            eventType: PaymentEventType::WEBHOOK_SESSION_EXPIRED,
            metadata: CheckoutSessionExpiredData::create($session->metadata?->toArray()),
        );
    }

}
