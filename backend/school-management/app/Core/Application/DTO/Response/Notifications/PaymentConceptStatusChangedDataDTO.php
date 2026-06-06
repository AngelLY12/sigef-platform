<?php

namespace App\Core\Application\DTO\Response\Notifications;

use App\Core\Application\DTO\Response\Notifications\Contracts\NotificationMetadata;
use App\Core\Domain\Enum\Notification\NotificationConceptPriority;
use Carbon\CarbonImmutable;

/**
 * @OA\Schema(
 *     schema="PaymentConceptStatusChangedData",
 *     type="object",
 *
 *     @OA\Property(property="concept_name", type="string", example="Inscripción"),
 *     @OA\Property(property="old_status", type="string", example="activo"),
 *     @OA\Property(property="new_status", type="string", example="finalizado"),
 *     @OA\Property(property="amount", type="string", example="1500.00"),
 *     @OA\Property(property="status_transition", type="string", example="activo_to_finalizado"),
 *     @OA\Property(
 *          property="priority",
 *          type="string",
 *          enum={"low","medium","high"}
 *      ),
 *     @OA\Property(
 *         property="timestamp",
 *         type="string",
 *         format="date-time",
 *         example="2026-06-04T18:30:00Z"
 *     )
 * )
 */
final readonly class PaymentConceptStatusChangedDataDTO implements NotificationMetadata
{
    public function __construct(
        public ?string $concept_name = null,
        public ?string $amount = null,
        public ?string $old_status = null,
        public ?string $new_status = null,
        public ?string $status_transition = null,
        public NotificationConceptPriority $priority,
        public ?CarbonImmutable $timestamp = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'concept_name' => $this->concept_name,
            'amount' => $this->amount,
            'old_status' => $this->old_status,
            'new_status' => $this->new_status,
            'status_transition' => $this->status_transition,
            'priority' => $this->priority->value,
            'timestamp' => $this->timestamp->toISOString(),
        ], fn ($value) => $value !== null);
    }

}
