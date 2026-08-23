<?php

namespace App\Core\Domain\ValueObjects\Payment\PaymentMethodDetails;

final readonly class CardPaymentMethodDetails implements PaymentMethodDetails
{
    public function __construct(
        public string $brand,
        public string $last4,
        public ?string $funding,
    ){}
    public function toArray(): array
    {
        return [
            'type' => 'tarjeta',
            'brand' => $this->brand,
            'last4' => $this->last4,
            'funding' => $this->funding,
        ];
    }
    public function type(): string
    {
        return 'tarjeta';
    }

}
