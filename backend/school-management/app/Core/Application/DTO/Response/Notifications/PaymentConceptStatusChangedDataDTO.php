<?php

namespace App\Core\Application\DTO\Response\Notifications;

use App\Core\Application\DTO\Response\Notifications\Contracts\NotificationMetadata;
use Carbon\CarbonImmutable;

/**
 * @OA\Schema(
 *     schema="PaymentConceptStatusChangedData",
 *     type="object",
 *
 *     @OA\Property(property="concept_id", type="integer", example=10),
 *     @OA\Property(property="concept_name", type="string", example="Inscripción"),
 *     @OA\Property(property="old_status", type="string", example="activo"),
 *     @OA\Property(property="new_status", type="string", example="finalizado"),
 *     @OA\Property(property="amount", type="string", example="1500.00"),
 *     @OA\Property(property="applies_to", type="string", example="students"),
 *     @OA\Property(property="status_transition", type="string", example="activo_to_finalizado"),
 *
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
        public ?int $concept_id = null,
        public ?string $concept_name = null,
        public ?string $old_status = null,
        public ?string $new_status = null,
        public ?string $amount = null,
        public ?string $applies_to = null,
        public ?string $status_transition = null,
        public ?CarbonImmutable $timestamp = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'concept_id' => $this->concept_id,
            'concept_name' => $this->concept_name,
            'old_status' => $this->old_status,
            'new_status' => $this->new_status,
            'amount' => $this->amount,
            'applies_to' => $this->applies_to,
            'status_transition' => $this->status_transition,
            'timestamp' => $this->timestamp->toISOString(),
        ], fn ($value) => $value !== null);
    }

}
