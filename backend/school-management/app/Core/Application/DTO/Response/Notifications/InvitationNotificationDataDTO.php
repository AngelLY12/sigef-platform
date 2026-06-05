<?php

namespace App\Core\Application\DTO\Response\Notifications;

use App\Core\Application\DTO\Response\Notifications\Contracts\NotificationMetadata;

/**
 * @OA\Schema(
 *     schema="InvitationNotificationData",
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
final readonly class InvitationNotificationDataDTO implements NotificationMetadata
{
    public function __construct(
        public ?string $student_name = null,
        public ?string $parent_name = null
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'student_name' => $this->student_name,
            'parent_name' => $this->parent_name,
        ], fn ($value) => $value !== null);
    }

}
