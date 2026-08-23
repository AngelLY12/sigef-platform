<?php

namespace App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\Charge;

use App\Core\Application\Factories\Payments\Stripe\StripePaymentMethodDetailsFactory;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\PaymentEventMetadata;
use App\Core\Domain\ValueObjects\Payment\PaymentMethodDetails\PaymentMethodDetails;
use App\Core\Domain\ValueObjects\Payment\Stripe\PaymentStripeMetadata;
use Stripe\Charge;

final readonly class ChargeSucceededData implements PaymentEventMetadata
{
    public function __construct(
        public string                $stripeEventType,
        public string                $chargeId,
        public ?string               $stripePaymentMethodId,
        public ?int                  $paymentMethodId,
        public int                   $amountCaptured,
        public int                   $amountRefunded,
        public ?PaymentMethodDetails $paymentMethodDetails,
        public ?string               $receiptUrl,
        public ?PaymentStripeMetadata                 $stripeMetadata,
    )
    {
    }

    public static function create(Charge $charge, ?int $paymentMethodId, ?PaymentMethodDetails $paymentMethodDetails): self
    {
        return new self(
            stripeEventType: 'charge.succeeded',
            chargeId: $charge->id,
            stripePaymentMethodId: $charge->payment_method ?? null,
            paymentMethodId: $paymentMethodId ?? null,
            amountCaptured: $charge->amount_captured,
            amountRefunded: $charge->amount_refunded,
            paymentMethodDetails: $paymentMethodDetails ?? null,
            receiptUrl: $charge->receipt_url ?? null,
            stripeMetadata: PaymentStripeMetadata::createFromArray($charge->metadata->toArray()) ?? null,

        );
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            stripeEventType: $data['stripe_event_type'],
            chargeId: $data['charge_id'],
            stripePaymentMethodId: $data['stripe_payment_method_id'] ?? null,
            paymentMethodId: $data['payment_method_id'] ?? null,
            amountCaptured: $data['amount_captured'],
            amountRefunded: $data['amount_refunded'],
            paymentMethodDetails: isset($data['payment_method_details'])
                ? StripePaymentMethodDetailsFactory::fromArray($data['payment_method_details'])
                : null,
            receiptUrl: $data['receipt_url'] ?? null,
            stripeMetadata: isset($data['stripe_metadata'])
                ? PaymentStripeMetadata::createFromArray($data['stripe_metadata'])
                : null,
        );
    }

    public function toArray(): array
    {
        return [
            'stripe_event_type' => $this->stripeEventType,
            'charge_id' => $this->chargeId,
            'stripe_payment_method_id' => $this->stripePaymentMethodId,
            'payment_method_id' => $this->paymentMethodId,
            'amount_captured' => $this->amountCaptured,
            'amount_refunded' => $this->amountRefunded,
            'payment_method_details' =>
                $this->paymentMethodDetails?->toArray(),
            'receipt_url' => $this->receiptUrl,
            'stripe_metadata' => $this->stripeMetadata?->toArray(),
        ];
    }
}
