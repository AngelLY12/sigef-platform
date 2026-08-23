<?php

namespace App\Core\Infraestructure\Mappers;

use App\Core\Domain\Entities\EmailEvent;
use App\Models\EmailEvent as EloquentEmailEvent;
class EmailEventMapper
{
    public static function toDomain(EloquentEmailEvent $event): EmailEvent
    {
        return new EmailEvent(
            id: $event->id,
            userId: $event->user_id,
            eventType: $event->event_type,
            recipientEmail: $event->recipient_email,
            status: $event->status,
            sourceType: $event->source_type,
            sourceId: $event->source_id,
            attemptCount: $event->attempt_count,
            errorMessage: $event->error_message,
            sentAt: $event->sent_at,
            deliveredAt: $event->delivered_at,
            failedAt: $event->failed_at,
            metadata: $event->metadata,
            createdAt: $event->created_at,
            updatedAt: $event->updated_at,
        );
    }

    public static function toPersistence(EmailEvent $event): array
    {
        return [
            'user_id' => $event->userId,
            'event_type' => $event->eventType->value,
            'recipient_email' => $event->recipientEmail,
            'status' => $event->status->value,
            'source_type' => $event->sourceType->value,
            'source_id' => $event->sourceId,
            'attempt_count' => $event->attemptCount,
            'error_message' => $event->errorMessage,
            'sent_at' => $event->sentAt,
            'delivered_at' => $event->deliveredAt,
            'failed_at' => $event->failedAt,
            'metadata' => $event->metadata,
        ];
    }

}
