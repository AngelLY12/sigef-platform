<?php

namespace App\Core\Domain\ValueObjects\Payment\PaymentMethodDetails;

final readonly class SpeiPaymentMethodDetails implements PaymentMethodDetails
{
    public function __construct(
        public ?string $bankName = null,
        public ?string $clabe = null,
        public ?string $reference = null,
    ) {}

    public function toArray(): array
    {
        return [
            'type' => 'spei',
            'bank_name' => $this->bankName,
            'clabe' => $this->clabe,
            'reference' => $this->reference,
        ];
    }

    public function type(): string
    {
        return 'spei';
    }

}
