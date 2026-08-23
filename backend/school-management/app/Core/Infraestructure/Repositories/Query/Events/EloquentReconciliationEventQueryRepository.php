<?php

namespace App\Core\Infraestructure\Repositories\Query\Events;

use App\Core\Application\DTO\Request\Events\Reconciliation\ReconciliationEventFilters;
use App\Core\Application\DTO\Response\Events\ReconcileEvent\ReconcileEventResponse;
use App\Core\Domain\Entities\ReconciliationEvent;
use App\Core\Domain\Enum\Events\Sources\ReconciliationSourceType;
use App\Core\Domain\Enum\Events\Status\ReconciliationEventStatus;
use App\Core\Domain\Repositories\Query\Events\ReconciliationEventQueryRepInterface;
use App\Core\Infraestructure\Mappers\ReconciliationEventMapper;
use App\Models\PaymentReconciliationEvent as EloquentReconciliationEvent;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EloquentReconciliationEventQueryRepository implements ReconciliationEventQueryRepInterface
{
    public function findById(int $id): ?ReconciliationEvent
    {
        $eloquent = EloquentReconciliationEvent::find($id);
        return $eloquent ? ReconciliationEventMapper::toDomain($eloquent) : null;
    }

    public function findBySource(ReconciliationSourceType $sourceType, string $sourceId): ?ReconciliationEvent
    {
        $eloquent = EloquentReconciliationEvent::where('source_id', $sourceId)
            ->where('source_type', $sourceType)
            ->first();
        return $eloquent ? ReconciliationEventMapper::toDomain($eloquent) : null;
    }

    public function findByPaymentIdAndEventTypeAndStatus(int $paymentId, ReconciliationEventStatus $status): ?ReconciliationEvent
    {
        $eloquent = EloquentReconciliationEvent::query()->where('payment_id', $paymentId)
            ->where('status', $status)
            ->first();

        return $eloquent ? ReconciliationEventMapper::toDomain($eloquent) : null;
    }

    public function findByPaymentEventTypeSource(int $paymentId, ReconciliationSourceType $sourceType, string $sourceId): ?ReconciliationEvent
    {
        $eloquent = EloquentReconciliationEvent::query()
            ->where('payment_id', $paymentId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->first();
        return $eloquent ? ReconciliationEventMapper::toDomain($eloquent) : null;
    }

    public function getAllReconciliationEvents(ReconciliationEventFilters $filters): LengthAwarePaginator
    {
        return EloquentReconciliationEvent::query()
            ->select([
                'payment_reconciliation_events.id',
                'payment_reconciliation_events.payment_id',
                'payment_reconciliation_events.status',
                'payment_reconciliation_events.source_type',
                'payment_reconciliation_events.source_id',
                'payment_reconciliation_events.created_at',
                'payments.concept_name'
            ])
            ->leftJoin(
                'payments',
                'payments.id',
                '=',
                'payment_reconciliation_events.payment_id'
            )
            ->when(
                $filters->paymentId,
                fn ($query, $paymentId) => $query->where('payment_reconciliation_events.payment_id', $paymentId)
            )
            ->when(
                $filters->sourceType,
                fn ($query, $sourceType) => $query->where('payment_reconciliation_events.source_type', $sourceType)
            )
            ->when(
                $filters->sourceId,
                fn ($query, $sourceId) => $query->where('payment_reconciliation_events.source_id', $sourceId)
            )
            ->when(
                $filters->status,
                fn ($query, $status) => $query->where('payment_reconciliation_events.status', $status)
            )
            ->when(
                $filters->from,
                fn ($query, $from) =>
                $query->where('payment_reconciliation_events.created_at', '>=', $from)
            )
            ->when(
                $filters->to,
                fn ($query, $to) =>
                $query->where('payment_reconciliation_events.created_at', '<=', $to)
            )
            ->orderByDesc('payment_reconciliation_events.created_at')
            ->orderByDesc('payment_reconciliation_events.id')
            ->paginate(
                perPage: $filters->perPage,
                page: $filters->page,
            )
            ->through(
                fn (EloquentReconciliationEvent $event) => ReconcileEventResponse::create($event)
            )
            ;
    }

    public function getPaymentReconciliationTimeline(int $paymentId): Collection
    {
        return EloquentReconciliationEvent::query()
            ->select([
                'payment_reconciliation_events.id',
                'payment_reconciliation_events.payment_id',
                'payment_reconciliation_events.status',
                'payment_reconciliation_events.source_type',
                'payment_reconciliation_events.source_id',
                'payment_reconciliation_events.created_at',
                'payments.concept_name'
            ])
            ->leftJoin(
                'payments',
                'payments.id',
                '=',
                'payment_reconciliation_events.payment_id'
            )
            ->where('payment_reconciliation_events.payment_id', $paymentId)
            ->orderByDesc('payment_reconciliation_events.created_at')
            ->orderByDesc('payment_reconciliation_events.id')
            ->get()
            ->map(
                fn (EloquentReconciliationEvent $event) =>
                ReconcileEventResponse::create($event)
            );
    }

}
