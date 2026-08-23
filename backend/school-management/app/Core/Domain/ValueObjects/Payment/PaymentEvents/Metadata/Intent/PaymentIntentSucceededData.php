<?php

namespace App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent;

use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\PaymentEventMetadata;
use App\Core\Domain\ValueObjects\Payment\Stripe\PaymentStripeMetadata;
use Stripe\PaymentIntent;

final readonly class PaymentIntentSucceededData implements PaymentEventMetadata
{
    public function __construct(
        public string $stripeEventType,
        public string $latestCharge,
        public ?PaymentStripeMetadata $stripeMetadata,
        public string $intentStatus
    ) {}

    public static function create(PaymentIntent $paymentIntent): self
    {
        return new self(
            stripeEventType: 'payment_intent.succeeded',
            latestCharge: $paymentIntent->latest_charge,
            stripeMetadata: PaymentStripeMetadata::createFromArray($paymentIntent->metadata->toArray()) ?? null,
            intentStatus: $paymentIntent->status,
        );
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            stripeEventType: $data['stripe_event_type'],
            latestCharge: $data['latest_charge'],
            stripeMetadata: isset($data['stripe_metadata']) ? PaymentStripeMetadata::createFromArray($data['stripe_metadata']) : null,
            intentStatus: $data['intent_status'],
        );
    }

    public function toArray(): array
    {
        return [
            'stripe_event_type' => $this->stripeEventType,
            'latest_charge' => $this->latestCharge,
            'stripe_metadata' => $this->stripeMetadata?->toArray(),
            'intent_status' => $this->intentStatus,
        ];
    }
}
