<?php

namespace App\Core\Domain\Repositories\Query\Events;

use App\Core\Application\DTO\Request\Events\Reconciliation\ReconciliationEventFilters;
use App\Core\Domain\Entities\ReconciliationEvent;
use App\Core\Domain\Enum\Events\Sources\ReconciliationSourceType;
use App\Core\Domain\Enum\Events\Status\ReconciliationEventStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ReconciliationEventQueryRepInterface
{
    public function findById(int $id): ?ReconciliationEvent;

    public function findBySource(
        ReconciliationSourceType $sourceType,
        string $sourceId,
    ): ?ReconciliationEvent;

    public function findByPaymentIdAndEventTypeAndStatus(int $paymentId, ReconciliationEventStatus $status): ?ReconciliationEvent;
    public function findByPaymentEventTypeSource(int $paymentId, ReconciliationSourceType $sourceType, string $sourceId): ?ReconciliationEvent;
    public function getAllReconciliationEvents(
        ReconciliationEventFilters $filters
    ): LengthAwarePaginator;
    public function getPaymentReconciliationTimeline(int $paymentId): Collection;


}
