<?php

namespace App\Core\Domain\ValueObjects\Payment\PaymentMethodDetails;

final readonly class GenericPaymentMethodDetails implements PaymentMethodDetails
{
    public function __construct(
        public ?string $type,
    ){}

    public function toArray(): array
    {
        return [
            'type' => $this->type,
        ];
    }

    public function type(): string
    {
        return $this->type;
    }

}
