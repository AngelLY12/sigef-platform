<?php

namespace App\Core\Domain\ValueObjects\Payment\Stripe;

final readonly class PaymentStripeMetadata
{
    public function __construct(
        public ?int $paymentId,
        public ?int $paymentConceptId,
        public ?string $conceptName,
        public ?int $userId,
    ) {}

    public static function createFromArray(array $data): self
    {
        return new self(
            paymentId: isset($data['payment_id']) ? (int) $data['payment_id'] : null,
            paymentConceptId: isset($data['payment_concept_id']) ? (int) $data['payment_concept_id'] : null,
            conceptName: $data['concept_name'] ?? null,
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
        );
    }

    public function toArray(): array
    {
        return [
            'payment_id' => $this->paymentId,
            'payment_concept_id' => $this->paymentConceptId,
            'concept_name' => $this->conceptName,
            'user_id' => $this->userId,
        ];
    }

}
