<?php

namespace App\Core\Application\Factories\Payments\Stripe;

use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use App\Core\Domain\Utils\Helpers\StripeHelper;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent\PaymentIntentCancelledData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent\PaymentIntentFailedData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent\PaymentIntentRequiresActionData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent\PaymentIntentSucceededData;
use App\Core\Domain\ValueObjects\Payment\Stripe\PaymentStripeMetadata;
use Stripe\PaymentIntent;

final class WebhookPaymentIntentEventFactory
{
    public static function succeeded(
        string $eventId,
        Payment $payment,
        PaymentIntent $paymentIntent,
    ): PaymentEvent {
        return PaymentEvent::createWebhookEvent(
            paymentId: $payment->id,
            stripeEventId: $eventId,
            paymentIntentId: $paymentIntent->id,
            sessionId: $payment->stripe_session_id,
            amount: StripeHelper::amountFromCents($paymentIntent->amount_received),
            eventType: PaymentEventType::WEBHOOK_PAYMENT_INTENT_SUCCEEDED,
            metadata: PaymentIntentSucceededData::create($paymentIntent),
        );
    }

    public static function failed(
        string $eventId,
        Payment $payment,
        PaymentIntent $paymentIntent,
    ): PaymentEvent {
        return PaymentEvent::createWebhookEvent(
            paymentId: $payment->id,
            stripeEventId: $eventId,
            paymentIntentId: $paymentIntent->id,
            sessionId: $payment->stripe_session_id,
            amount: '0.00',
            eventType: PaymentEventType::WEBHOOK_PAYMENT_FAILED,
            metadata: PaymentIntentFailedData::create($paymentIntent),
        );
    }

    public static function requiresAction(
        string $eventId,
        Payment $payment,
        PaymentIntent $paymentIntent,
    ): PaymentEvent {
        return PaymentEvent::createWebhookEvent(
            paymentId: $payment->id,
            stripeEventId: $eventId,
            paymentIntentId: $paymentIntent->id,
            sessionId: $payment->stripe_session_id,
            amount: '0.00',
            eventType: PaymentEventType::WEBHOOK_PAYMENT_REQUIRES_ACTION,
            metadata: PaymentIntentRequiresActionData::create(
                intentMetadata:
                PaymentStripeMetadata::
                createFromArray($paymentIntent->metadata->toArray()),
                actionDetails:
                RequiredActionDetailsFactory::
                fromStripe($paymentIntent)),
        );
    }

    public static function cancelled(
        string $eventId,
        Payment $payment,
        PaymentIntent $paymentIntent,
    ): PaymentEvent {
        return PaymentEvent::createWebhookEvent(
            paymentId: $payment->id,
            stripeEventId: $eventId,
            paymentIntentId: $paymentIntent->id,
            sessionId: $payment->stripe_session_id,
            amount: '0.00',
            eventType: PaymentEventType::WEBHOOK_PAYMENT_CANCELLED,
            metadata: PaymentIntentCancelledData::create($paymentIntent)
        );
    }
}
