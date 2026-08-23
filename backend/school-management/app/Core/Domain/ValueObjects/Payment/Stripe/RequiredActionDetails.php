<?php

namespace App\Core\Domain\ValueObjects\Payment\Stripe;

final readonly class RequiredActionDetails
{
    public function __construct(
        public string $type,
        public string $reference,
        public ?string $url,
        public ?int $expiresAfterDays,
    ) {}

    public static function createFromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            reference: $data['reference'],
            url: $data['url'] ?? null,
            expiresAfterDays: $data['expires_after_days'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'reference' => $this->reference,
            'url' => $this->url,
            'expires_after_days' => $this->expiresAfterDays,
        ];
    }

}
