<?php

namespace App\Core\Domain\Entities;

use App\Core\Domain\Enum\Events\ReconciliationOutcome;
use App\Core\Domain\Enum\Events\Sources\ReconciliationSourceType;
use App\Core\Domain\Enum\Events\Status\ReconciliationEventStatus;
use App\Core\Domain\ValueObjects\Payment\ReconciliationEvents\ReconciliationEventMetadata;

/**
 * @OA\Schema(
 *     schema="ReconciliationEvent",
 *     type="object",
 *     required={"status","sourceType","sourceId"},
 *     @OA\Property(property="id", type="integer", nullable=true),
 *     @OA\Property(property="paymentId", type="integer", nullable=true),
 *     @OA\Property(property="outcome", ref="#/components/schemas/ReconciliationOutcome", nullable=true),
 *     @OA\Property(property="status", ref="#/components/schemas/ReconciliationEventStatus"),
 *     @OA\Property(property="sourceType", ref="#/components/schemas/ReconciliationSourceType"),
 *     @OA\Property(property="sourceId", type="string"),
 *     @OA\Property(property="errorMessage", type="string", nullable=true),
 *     @OA\Property(property="metadata", type="object", nullable=true),
 *     @OA\Property(property="startedAt", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="completedAt", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="failedAt", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="createdAt", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="updatedAt", type="string", format="date-time", nullable=true),
 * )
 */
class ReconciliationEvent
{
    public function __construct(
        public ?int $id = null,
        public ?int $paymentId = null,
        public ?ReconciliationOutcome $outcome = null,
        public ReconciliationEventStatus $status = ReconciliationEventStatus::PENDING,
        public ReconciliationSourceType $sourceType,
        public string $sourceId,
        public ?string $errorMessage = null,
        public ?ReconciliationEventMetadata $metadata = null,
        public ?\DateTime $startedAt = null,
        public ?\DateTime $completedAt = null,
        public ?\DateTime $failedAt = null,
        public ?\DateTime $createdAt = null,
        public ?\DateTime $updatedAt = null,
    ) {}

    public static function create(
        ?int $paymentId,
        ReconciliationSourceType $sourceType,
        string $sourceId,
    ): self {
        return new self(
            id: null,
            paymentId: $paymentId,
            outcome: null,
            status: ReconciliationEventStatus::PENDING,
            sourceType: $sourceType,
            sourceId: $sourceId,
            errorMessage: null,
            metadata: null,
            startedAt: new \DateTime(),
            completedAt: null,
            failedAt: null,
            createdAt: null,
            updatedAt: null,
        );
    }

    public function complete(
        ReconciliationOutcome $outcome,
        ?ReconciliationEventMetadata $metadata,
    ): void {
        $this->status = ReconciliationEventStatus::COMPLETED;
        $this->outcome = $outcome;
        $this->completedAt = new \DateTime();
        $this->metadata = $metadata ?? null;
    }

    public function fail(
        string $errorMessage,
        ReconciliationOutcome $outcome,
    ): void {
        $this->status = ReconciliationEventStatus::FAILED;
        $this->outcome = $outcome;
        $this->errorMessage = $errorMessage;
        $this->failedAt = new \DateTime();
        $this->metadata = null;
    }
}
