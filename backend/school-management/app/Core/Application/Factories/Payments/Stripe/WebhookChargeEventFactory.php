<?php

namespace App\Core\Application\Factories\Payments\Stripe;

use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use App\Core\Domain\Utils\Helpers\StripeHelper;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Charge\ChargeSucceededData;
use App\Core\Domain\ValueObjects\Payment\PaymentMethodDetails\PaymentMethodDetails;
use Stripe\Charge;

final class WebhookChargeEventFactory
{
    public static function succeeded(
        string $eventId,
        Payment $payment,
        Charge $charge,
        ?int $paymentMethodId = null,
        ?PaymentMethodDetails $paymentMethodDetails = null,
    ): PaymentEvent {
        return PaymentEvent::createWebhookEvent(
            paymentId: $payment->id,
            stripeEventId: $eventId,
            paymentIntentId: $charge->payment_intent,
            sessionId: $payment->stripe_session_id,
            amount: StripeHelper::amountFromCents($charge->amount_captured),
            eventType: PaymentEventType::WEBHOOK_CHARGE_SUCCEEDED,
            metadata: ChargeSucceededData::create(charge: $charge, paymentMethodId: $paymentMethodId, paymentMethodDetails: $paymentMethodDetails),
        );
    }
}
