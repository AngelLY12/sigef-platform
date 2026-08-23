<?php

namespace App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Session;

use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\PaymentEventMetadata;
use App\Core\Domain\ValueObjects\Payment\Stripe\PaymentStripeMetadata;

final readonly class CheckoutSessionExpiredData implements PaymentEventMetadata
{
    public function __construct(
        public string $stripeEventType,
        public ?PaymentStripeMetadata $stripeMetadata,
    ){}

    public static function create(array $metadata): self
    {
        return new self(
            stripeEventType: 'checkout.session.expired',
            stripeMetadata: PaymentStripeMetadata::createFromArray($metadata) ?? null
        );
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            stripeEventType: $data['stripe_event_type'],
            stripeMetadata: isset($data['stripe_metadata']) ? PaymentStripeMetadata::createFromArray($data['stripe_metadata']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'stripe_event_type' => $this->stripeEventType,
            'stripe_metadata' => $this->stripeMetadata?->toArray(),
        ];
    }

}
