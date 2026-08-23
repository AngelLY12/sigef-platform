<?php

namespace App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Intent;

use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\PaymentEventMetadata;
use App\Core\Domain\ValueObjects\Payment\Stripe\PaymentStripeMetadata;
use Stripe\PaymentIntent;

final readonly class PaymentIntentFailedData implements PaymentEventMetadata
{
    public function __construct(
        public string $stripeEventType,
        public ?string $stripeErrorCode,
        public ?string $stripeDeclineCode,
        public ?string $stripeErrorMessage,
        public ?string $stripeErrorType,
        public string $latestCharge,
        public ?PaymentStripeMetadata $stripeMetadata,
    ){}

    public static function create(PaymentIntent $paymentIntent): self
    {
        return new self(
            stripeEventType: 'payment_intent.payment_failed',
            stripeErrorCode: $paymentIntent->last_payment_error->code,
            stripeDeclineCode: $paymentIntent->last_payment_error->decline_code,
            stripeErrorMessage: $paymentIntent->last_payment_error->message,
            stripeErrorType: $paymentIntent->last_payment_error->type,
            latestCharge: $paymentIntent->latest_charge,
            stripeMetadata: PaymentStripeMetadata::createFromArray($paymentIntent->metadata?->toArray()) ?? null,
        );
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            stripeEventType: $data['stripe_event_type'],
            stripeErrorCode: $data['stripe_error_code'],
            stripeDeclineCode: $data['stripe_decline_code'],
            stripeErrorMessage: $data['stripe_error_message'],
            stripeErrorType: $data['stripe_error_type'],
            latestCharge: $data['latest_charge'],
            stripeMetadata: isset($data['stripe_metadata']) ? PaymentStripeMetadata::createFromArray($data['stripe_metadata']): null,
        );
    }

    public function toArray(): array
    {
        return [
            'stripe_event_type' => $this->stripeEventType,
            'stripe_error_code' => $this->stripeErrorCode,
            'stripe_decline_code' => $this->stripeDeclineCode,
            'stripe_error_message' => $this->stripeErrorMessage,
            'stripe_error_type' => $this->stripeErrorType,
            'latest_charge' => $this->latestCharge,
            'stripe_metadata' => $this->stripeMetadata?->toArray(),
        ];

    }
}
