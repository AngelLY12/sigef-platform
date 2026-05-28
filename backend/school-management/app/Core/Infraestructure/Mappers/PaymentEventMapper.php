<?php

namespace App\Core\Infraestructure\Mappers;

use App\Core\Domain\Entities\PaymentEvent;
use App\Models\PaymentEvent as EloquentPaymentEvent;
class PaymentEventMapper
{
    public static function toDomain(EloquentPaymentEvent $model): PaymentEvent
    {
        return new PaymentEvent(
            id: $model->id,
            paymentId: $model->payment_id,
            stripeEventId: $model->stripe_event_id,
            stripePaymentIntentId: $model->stripe_payment_intent_id,
            stripeSessionId: $model->stripe_session_id,
            eventType: $model->event_type,
            metadata: $model->metadata,
            amountReceived: $model->amount_received,
            status: $model->status,
            processed: $model->processed,
            errorMessage: $model->error_message,
            retryCount: $model->retry_count,
            processedAt: $model->processed_at,
            createdAt: $model->created_at,
            updatedAt: $model->updated_at
        );
    }

    public static function toEloquent(PaymentEvent $entity): array
    {
        return [
            'payment_id' => $entity->paymentId,
            'stripe_event_id' => $entity->stripeEventId,
            'stripe_payment_intent_id' => $entity->stripePaymentIntentId,
            'stripe_session_id' => $entity->stripeSessionId,
            'event_type' => $entity->eventType,
            'metadata' => $entity->metadata,
            'amount_received' => $entity->amountReceived,
            'status' => $entity->status,
            'processed' => $entity->processed,
            'error_message' => $entity->errorMessage,
            'retry_count' => $entity->retryCount,
            'processed_at' => $entity->processedAt,
        ];
    }

}
