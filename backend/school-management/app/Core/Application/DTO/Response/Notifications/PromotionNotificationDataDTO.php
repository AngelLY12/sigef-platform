<?php

namespace App\Core\Application\DTO\Response\Notifications;

use App\Core\Application\DTO\Response\Notifications\Contracts\NotificationMetadata;

/**
 * @OA\Schema(
 *     schema="PromotionNotificationData",
 *     type="object",
 *
 *     @OA\Property(
 *         property="promoted_count",
 *         type="integer",
 *         example=120
 *     ),
 *
 *     @OA\Property(
 *         property="deactivated_count",
 *         type="integer",
 *         example=5
 *     ),
 *
 *     @OA\Property(
 *         property="error",
 *         type="string",
 *         nullable=true,
 *         example="Error al actualizar el semestre"
 *     )
 * )
 */
final readonly class PromotionNotificationDataDTO implements NotificationMetadata
{
    public function __construct(
        public ?int $promoted_count = null,
        public ?int $deactivated_count = null,
        public ?string $error = null
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'promoted_count' => $this->promoted_count,
            'deactivated' => $this->deactivated_count,
            'error' => $this->error,
        ], fn ($value) => $value !== null);
    }

}
