<?php

namespace App\Core\Domain\ValueObjects\Payment\ReconciliationEvents;

use App\Core\Domain\Enum\Events\Sources\ReconciliationDataSource;

final readonly class ReconciliationMatchedData implements ReconciliationEventMetadata
{
    public function __construct(
        public ?ReconciliationDataSource $dataSource,
    ) {}

    public static function create(ReconciliationDataSource $dataSource): self
    {
        return new self($dataSource);
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            dataSource: isset($data['data_source']) ? ReconciliationDataSource::from($data['data_source']): null,
        );
    }

    public function toArray(): array
    {
        return [
            'data_source' => $this->dataSource->value,
        ];
    }

}
