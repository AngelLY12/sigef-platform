<?php

namespace App\Core\Domain\ValueObjects\Payment\ReconciliationEvents;

use App\Core\Domain\Enum\Events\Sources\ReconciliationDataSource;

final readonly class ReconciliationCorrectedData implements ReconciliationEventMetadata
{
    public function __construct(
        public ?ReconciliationDataSource $dataSource,
        public ?string                   $amountReceived,
        public ?string                  $paymentIntentId = null,
        public ?string                  $chargeId = null,
    )
    {
    }

    public static function create(
        ReconciliationDataSource $dataSource,
        string  $amountReceived,
        ?string $paymentIntentId = null,
        ?string $chargeId = null): self
    {
        return new self(
            dataSource: $dataSource,
            amountReceived: $amountReceived,
            paymentIntentId: $paymentIntentId,
            chargeId: $chargeId
        );
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            dataSource: isset($data['data_source']) ? ReconciliationDataSource::from($data['data_source']): null,
            amountReceived: $data['amount_received'] ?? null,
            paymentIntentId: $data['payment_intent_id'] ?? null,
            chargeId: $data['charge_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'data_source' => $this->dataSource->value,
            'amount_received' => $this->amountReceived,
            'payment_intent_id' => $this->paymentIntentId,
            'charge_id' => $this->chargeId,
        ];
    }

}
