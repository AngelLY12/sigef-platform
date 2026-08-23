<?php

namespace App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent;

use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\PaymentEventMetadata;
use App\Core\Domain\ValueObjects\Payment\Stripe\PaymentStripeMetadata;
use Stripe\PaymentIntent;

final readonly class PaymentIntentCancelledData implements PaymentEventMetadata
{
    public function __construct(
        public string $stripeEventType,
        public string $cancellationReason,
        public ?PaymentStripeMetadata $stripeMetadata,
    ){}

    public static function create(PaymentIntent  $paymentIntent): self
    {
        return new self(
            stripeEventType: 'payment_intent.canceled',
            cancellationReason: $paymentIntent->cancellation_reason,
            stripeMetadata: PaymentStripeMetadata::createFromArray($paymentIntent->metadata?->toArray()) ?? null,
        );
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            stripeEventType: $data['stripe_event_type'],
            cancellationReason: $data['cancellation_reason'],
            stripeMetadata: isset($data['stripe_metadata'])
                ? PaymentStripeMetadata::createFromArray($data['stripe_metadata'])
                : null,
        );
    }

    public function toArray(): array
    {
        return [
            'stripe_event_type' => $this->stripeEventType,
            'cancellation_reason' => $this->cancellationReason,
            'stripe_metadata' => $this->stripeMetadata?->toArray(),
        ];

    }

}
