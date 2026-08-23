<?php

namespace App\Core\Application\DTO\Request\Events\EmailEvent;

use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Status\EmailEventStatus;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use Carbon\Carbon;

class EmailEventHistoryFilters
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
        public ?EmailEventType $eventType = null,
        public ?EmailEventStatus $status = null,
        public ?EmailEventSourceType $sourceType = null,
        public ?Carbon $from = null,
        public ?Carbon $to = null,
    ){}

    public static function create(
        int $page = 1,
        int $perPage = 20,
        ?EmailEventType $eventType = null,
        ?EmailEventStatus $status = null,
        ?EmailEventSourceType $sourceType = null,
        ?Carbon $from = null,
        ?Carbon $to = null,
    ): self {
        return new self(
            page: $page,
            perPage: $perPage,
            eventType: $eventType,
            status: $status,
            sourceType: $sourceType,
            from: $from,
            to: $to,
        );
    }

    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'perPage' => $this->perPage,
            'eventType' => $this->eventType?->value,
            'status' => $this->status?->value,
            'sourceType' => $this->sourceType?->value,
            'from' => $this->from?->format('Y-m-d H:i:s'),
            'to' => $this->to?->format('Y-m-d H:i:s'),
        ];
    }

}
