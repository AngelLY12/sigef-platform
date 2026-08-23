<?php

namespace App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent;

use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\PaymentEventMetadata;
use App\Core\Domain\ValueObjects\Payment\Stripe\PaymentStripeMetadata;
use App\Core\Domain\ValueObjects\Payment\Stripe\RequiredActionDetails;

final readonly class PaymentIntentRequiresActionData implements PaymentEventMetadata
{
    public function __construct(
        public string $stripeEventType,
        public ?RequiredActionDetails $requiredAction,
        public ?PaymentStripeMetadata $stripeMetadata,
    ){}


    public static function create(?PaymentStripeMetadata $intentMetadata, ?RequiredActionDetails $actionDetails): self
    {
        return new self(
            stripeEventType: 'payment_intent.requires_action',
            requiredAction: $actionDetails ?? null,
            stripeMetadata: $intentMetadata ?? null,
        );
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            stripeEventType: $data['stripe_event_type'],
            requiredAction: isset($data['required_action']) ? RequiredActionDetails::createFromArray($data['required_action']) : null,
            stripeMetadata: isset($data['stripe_metadata']) ? PaymentStripeMetadata::createFromArray($data['stripe_metadata']) : null,

        );
    }

    public function toArray(): array
    {
        return [
            'stripe_event_type' => $this->stripeEventType,
            'required_action' => $this->requiredAction?->toArray(),
            'stripe_metadata' => $this->stripeMetadata?->toArray(),
        ];

    }
}
