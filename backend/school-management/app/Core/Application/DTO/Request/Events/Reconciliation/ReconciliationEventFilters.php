<?php

namespace App\Core\Application\DTO\Request\Events\Reconciliation;

use App\Core\Domain\Enum\Events\Sources\ReconciliationSourceType;
use App\Core\Domain\Enum\Events\Status\ReconciliationEventStatus;
use Carbon\Carbon;

class ReconciliationEventFilters
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
        public ?int $paymentId = null,
        public ?ReconciliationSourceType $sourceType = null,
        public ?string $sourceId = null,
        public ?ReconciliationEventStatus $status = null,
        public ?Carbon $from = null,
        public ?Carbon $to = null,
    ){}

    public static function create(
        int $page = 1,
        int $perPage = 20,
        ?int $paymentId = null,
        ?ReconciliationSourceType $sourceType = null,
        ?string $sourceId = null,
        ?ReconciliationEventStatus $status = null,
        ?Carbon $from = null,
        ?Carbon $to = null,
    ): self {
        return new self(
            page: $page,
            perPage: $perPage,
            paymentId: $paymentId,
            sourceType: $sourceType,
            sourceId: $sourceId,
            status: $status,
            from: $from,
            to: $to,
        );
    }

    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'perPage' => $this->perPage,
            'paymentId' => $this->paymentId,
            'sourceType' => $this->sourceType?->value,
            'sourceId' => $this->sourceId,
            'status' => $this->status?->value,
            'from' => $this->from?->format('Y-m-d H:i:s'),
            'to' => $this->to?->format('Y-m-d H:i:s'),
        ];
    }


}
