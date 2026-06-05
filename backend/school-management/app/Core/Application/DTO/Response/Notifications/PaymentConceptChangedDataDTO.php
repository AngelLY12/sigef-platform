<?php

namespace App\Core\Application\DTO\Response\Notifications;

use App\Core\Application\DTO\Response\Notifications\Contracts\NotificationMetadata;
use Carbon\CarbonImmutable;

/**
 * @OA\Schema(
 *     schema="PaymentConceptChangedData",
 *     type="object",
 *
 *     @OA\Property(property="concept_id", type="integer", example=10),
 *     @OA\Property(property="concept_name", type="string", example="Inscripción"),
 *     @OA\Property(property="amount", type="string", example="1500.00"),
 *
 *     @OA\Property(
 *         property="changes",
 *         type="array",
 *         @OA\Items(type="object")
 *     ),
 *
 *     @OA\Property(
 *         property="action",
 *         type="string",
 *         example="created_concept"
 *     ),
 *
 *     @OA\Property(
 *         property="timestamp",
 *         type="string",
 *         format="date-time",
 *         example="2026-06-04T18:30:00Z"
 *     ),
 *
 *     @OA\Property(
 *         property="start_date",
 *         type="string",
 *         format="date-time",
 *         nullable=true
 *     ),
 *
 *     @OA\Property(
 *         property="end_date",
 *         type="string",
 *         format="date-time",
 *         nullable=true
 *     ),
 *
 *     @OA\Property(
 *         property="priority",
 *         type="string",
 *         enum={"low","medium","high"}
 *     )
 * )
 */
final readonly class PaymentConceptChangedDataDTO implements NotificationMetadata
{
    public function __construct(
        public ?int $concept_id = null,
        public ?string $concept_name = null,
        public ?string $amount = null,
        public ?array $changes = null,
        public ?string $action = null,
        public ?CarbonImmutable $timestamp = null,
        public ?CarbonImmutable $start_date = null,
        public ?CarbonImmutable $end_date = null,
        public ?string $priority = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'concept_id' => $this->concept_id,
            'concept_name' => $this->concept_name,
            'amount' => $this->amount,
            'changes' => $this->changes,
            'action' => $this->action,
            'timestamp' => $this->timestamp->toISOString(),
            'start_date' => $this->start_date->toISOString(),
            'end_date' => $this->end_date->toISOString(),
            'priority' => $this->priority
        ], fn ($value) => $value !== null);
    }

}
