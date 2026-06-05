<?php

namespace App\Core\Application\DTO\Response\Notifications;

use App\Core\Application\DTO\Response\Notifications\Contracts\NotificationMetadata;

/**
 * @OA\Schema(
 *     schema="RelationNotificationData",
 *     type="object",
 *
 *     @OA\Property(
 *         property="student_name",
 *         type="string",
 *         example="Juan Pérez"
 *     ),
 *
 *     @OA\Property(
 *         property="parent_name",
 *         type="string",
 *         example="María Pérez"
 *     )
 * )
 */
final readonly class RelationNotificationDataDTO implements NotificationMetadata
{
    public function __construct(
        public string $student_name,
        public string $parent_name,
    ) {}

    public function toArray(): array
    {
        return [
            'student_name' => $this->student_name,
            'parent_name' => $this->parent_name,
        ];
    }
}
