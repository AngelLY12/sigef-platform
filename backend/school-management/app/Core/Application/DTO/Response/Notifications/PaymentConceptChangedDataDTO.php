<?php

namespace App\Core\Application\DTO\Response\Notifications;

use App\Core\Application\DTO\Response\Notifications\Contracts\NotificationMetadata;
use App\Core\Domain\Enum\Notification\NotificationConceptAction;
use App\Core\Domain\Enum\Notification\NotificationConceptPriority;
use Carbon\CarbonImmutable;

/**
 * @OA\Schema(
 *     schema="PaymentConceptChangedData",
 *     type="object",
 *
 *     @OA\Property(property="concept_name", type="string", example="Inscripción"),
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
 *         property="priority",
 *         type="string",
 *         enum={"low","medium","high"}
 *     )
 * )
 */
final readonly class PaymentConceptChangedDataDTO implements NotificationMetadata
{
    public function __construct(
        public ?string $concept_name = null,
        public ?array $changes = null,
        public ?NotificationConceptAction $action = null,
        public ?CarbonImmutable $timestamp = null,
        public ?NotificationConceptPriority $priority = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'concept_name' => $this->concept_name,
            'changes' => $this->changes,
            'action' => $this->action->value,
            'timestamp' => $this->timestamp->toISOString(),
            'priority' => $this->priority->value
        ], fn ($value) => $value !== null);
    }

}
