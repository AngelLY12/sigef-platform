<?php

namespace App\Core\Domain\Entities;

use App\Core\Domain\Enum\Events\Sources\EmailEventSourceType;
use App\Core\Domain\Enum\Events\Status\EmailEventStatus;
use App\Core\Domain\Enum\Events\Types\EmailEventType;
use App\Core\Domain\ValueObjects\EmailEvent\EmailEventMetadata;

/**
 * @OA\Schema(
 *     schema="EmailEvent",
 *     type="object",
 *     required={"eventType","recipientEmail","status","sourceType","sourceId","attemptCount"},
 *     @OA\Property(property="id", type="integer", nullable=true),
 *     @OA\Property(property="userId", type="integer", nullable=true),
 *     @OA\Property(property="eventType", ref="#/components/schemas/EmailEventType"),
 *     @OA\Property(property="recipientEmail", type="string", format="email"),
 *     @OA\Property(property="status", ref="#/components/schemas/EmailEventStatus"),
 *     @OA\Property(property="sourceType", ref="#/components/schemas/EmailEventSourceType"),
 *     @OA\Property(property="sourceId", type="string"),
 *     @OA\Property(property="attemptCount", type="integer"),
 *     @OA\Property(property="errorMessage", type="string", nullable=true),
 *     @OA\Property(property="sentAt", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="deliveredAt", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="failedAt", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="metadata", type="object", nullable=true),
 *     @OA\Property(property="createdAt", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updatedAt", type="string", format="date-time", nullable=true),
 * )
 */
class EmailEvent
{
    public function __construct(
        public ?int                 $id = null,
        public ?int                 $userId = null,
        public EmailEventType       $eventType,
        public string               $recipientEmail,
        public EmailEventStatus     $status = EmailEventStatus::PENDING,
        public EmailEventSourceType $sourceType,
        public string               $sourceId,
        public int                  $attemptCount = 0,
        public ?string              $errorMessage = null,
        public ?\DateTime           $sentAt = null,
        public ?\DateTime           $deliveredAt = null,
        public ?\DateTime           $failedAt = null,
        public ?EmailEventMetadata  $metadata = null,
        public ?\DateTime           $createdAt = null,
        public ?\DateTime           $updatedAt = null,
    )
    {
    }

    public static function createEmailEvent(
        ?int                 $userId,
        EmailEventType       $eventType,
        string               $recipientEmail,
        EmailEventSourceType $sourceType,
        string               $sourceId,
        ?EmailEventMetadata  $metadata,
    ): self
    {
        return new self(
            id: null,
            userId: $userId,
            eventType: $eventType,
            recipientEmail: $recipientEmail,
            status: EmailEventStatus::PENDING,
            sourceType: $sourceType,
            sourceId: $sourceId,
            attemptCount: 0,
            errorMessage: null,
            sentAt: null,
            deliveredAt: null,
            failedAt: null,
            metadata: $metadata,
            createdAt: null,
            updatedAt: null,
        );
    }

    public function alreadySent(): bool
    {
        return $this->sentAt !== null || $this->status === EmailEventStatus::SENT;
    }

    public function alreadyDelivered(): bool
    {
        return $this->deliveredAt !== null || $this->status === EmailEventStatus::DELIVERED;

    }

    public function markAsSent(): void
    {
        $this->status = EmailEventStatus::SENT;
        $this->sentAt = new \DateTime();
        $this->errorMessage = null;
    }

    public function markAsDelivered(): void
    {
        $this->status = EmailEventStatus::DELIVERED;
        $this->deliveredAt = new \DateTime();
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->status = EmailEventStatus::FAILED;
        $this->errorMessage = $errorMessage;
        $this->failedAt = new \DateTime();
    }

    public function registerAttempt(): void
    {
        $this->attemptCount++;
    }

}
