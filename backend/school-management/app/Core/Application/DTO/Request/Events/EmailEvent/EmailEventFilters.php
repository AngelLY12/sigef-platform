<?php

namespace App\Core\Application\DTO\Request\Events\EmailEvent;

use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Status\EmailEventStatus;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use Carbon\Carbon;

class EmailEventFilters
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
        public ?int $userId = null,
        public ?EmailEventType $eventType = null,
        public ?EmailEventStatus $status = null,
        public ?string $recipientEmail = null,
        public ?EmailEventSourceType $sourceType = null,
        public ?string $sourceId = null,
        public ?Carbon $from = null,
        public ?Carbon $to = null,
    ) {}

    public static function create(
        int $page = 1,
        int $perPage = 20,
        ?int $userId = null,
        ?EmailEventType $eventType = null,
        ?EmailEventStatus $status = null,
        ?string $recipientEmail = null,
        ?EmailEventSourceType $sourceType = null,
        ?string $sourceId = null,
        ?Carbon $from = null,
        ?Carbon $to = null,
    ): self {
        return new self(
            page: $page,
            perPage: $perPage,
            userId: $userId,
            eventType: $eventType,
            status: $status,
            recipientEmail: $recipientEmail,
            sourceType: $sourceType,
            sourceId: $sourceId,
            from: $from,
            to: $to,
        );
    }

    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'perPage' => $this->perPage,
            'userId' => $this->userId,
            'eventType' => $this->eventType?->value,
            'status' => $this->status?->value,
            'recipientEmail' => $this->recipientEmail,
            'sourceType' => $this->sourceType?->value,
            'sourceId' => $this->sourceId,
            'from' => $this->from?->format('Y-m-d H:i:s'),
            'to' => $this->to?->format('Y-m-d H:i:s'),
        ];
    }

}
