<?php

namespace App\Core\Application\Factories\Payments\Stripe;

use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Charge\ChargeSucceededData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent\PaymentIntentCancelledData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent\PaymentIntentFailedData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent\PaymentIntentRequiresActionData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent\PaymentIntentSucceededData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\PaymentEventMetadata;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Session\CheckoutSessionAsyncCompletedData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Session\CheckoutSessionCompletedData;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Session\CheckoutSessionExpiredData;

final class PaymentEventMetadataFactory
{
    public static function fromArray(array $data): PaymentEventMetadata
    {
        return match ($data['stripe_event_type'] ?? null) {
            'charge.succeeded' =>
            ChargeSucceededData::createFromArray($data),

            'payment_intent.succeeded' =>
            PaymentIntentSucceededData::createFromArray($data),

            'payment_intent.payment_failed' =>
            PaymentIntentFailedData::createFromArray($data),

            'payment_intent.requires_action' =>
            PaymentIntentRequiresActionData::createFromArray($data),

            'payment_intent.canceled' =>
            PaymentIntentCancelledData::createFromArray($data),

            'checkout.session.completed' =>
            CheckoutSessionCompletedData::createFromArray($data),

            'checkout.session.async_payment_succeeded' =>
            CheckoutSessionAsyncCompletedData::createFromArray($data),

            'checkout.session.expired' =>
            CheckoutSessionExpiredData::createFromArray($data),

            default => throw new \InvalidArgumentException(
                'Tipo de evento Stripe no soportado: ' .
                ($data['stripe_event_type'] ?? 'null')
            ),
        };
    }

}
