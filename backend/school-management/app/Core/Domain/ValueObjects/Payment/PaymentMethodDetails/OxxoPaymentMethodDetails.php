<?php

namespace App\Core\Domain\ValueObjects\Payment\PaymentMethodDetails;

final readonly class OxxoPaymentMethodDetails implements PaymentMethodDetails
{
    public function __construct(
        public ?string $reference,
        public ?int $expiresAfter,
    ){}

    public function toArray(): array
    {
        return [
            'type' => 'oxxo',
            'reference' => $this->reference,
            'expires_after' => $this->expiresAfter,
        ];
    }

    public function type(): string
    {
        return 'oxxo';
    }

}
