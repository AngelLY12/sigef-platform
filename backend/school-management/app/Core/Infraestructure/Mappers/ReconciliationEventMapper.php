<?php

namespace App\Core\Infraestructure\Mappers;
use App\Core\Domain\Entities\ReconciliationEvent;
use App\Models\PaymentReconciliationEvent as EloquentReconciliationEvent;

class ReconciliationEventMapper
{
    public static function toDomain(EloquentReconciliationEvent $event): ReconciliationEvent
    {
        return new ReconciliationEvent(
            id: $event->id,
            paymentId: $event->payment_id,
            outcome: $event->outcome,
            status: $event->status,
            sourceType: $event->source_type,
            sourceId: $event->source_id,
            errorMessage: $event->error_message,
            metadata: $event->metadata,
            startedAt: $event->started_at,
            completedAt: $event->completed_at,
            failedAt: $event->failed_at,
            createdAt: $event->created_at,
            updatedAt: $event->updated_at,

        );
    }

    public static function toPersistence(ReconciliationEvent $event): array
    {
        return [
            'payment_id' => $event->paymentId,
            'outcome' => $event->outcome?->value,
            'status' => $event->status->value,
            'source_type' => $event->sourceType->value,
            'source_id' => $event->sourceId,
            'error_message' => $event->errorMessage,
            'metadata' => $event->metadata,
            'started_at' => $event->startedAt,
            'completed_at' => $event->completedAt,
            'failed_at' => $event->failedAt,
        ];
    }

}
