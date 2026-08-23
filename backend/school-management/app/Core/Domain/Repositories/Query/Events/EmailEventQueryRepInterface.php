<?php

namespace App\Core\Domain\Repositories\Query\Events;

use App\Core\Application\DTO\Request\Events\EmailEvent\EmailEventFilters;
use App\Core\Application\DTO\Request\Events\EmailEvent\EmailEventHistoryFilters;
use App\Core\Domain\Entities\EmailEvent;
use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface EmailEventQueryRepInterface
{
    public function findById(int $id): ?EmailEvent;
    public function findBySource(
        EmailEventSourceType $sourceType,
        string $sourceId,
        EmailEventType $eventType,
    ): ?EmailEvent;

    public function getAllEmailEvents(EmailEventFilters $filters): LengthAwarePaginator;
    public function getUserEmailHistory(EmailEventHistoryFilters $filters, int $userId): LengthAwarePaginator;
}
