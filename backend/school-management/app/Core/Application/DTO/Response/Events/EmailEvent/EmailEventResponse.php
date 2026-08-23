<?php

namespace App\Core\Application\DTO\Response\Events\EmailEvent;


use App\Models\EmailEvent;

/**
 * @OA\Schema(
 *     schema="EmailEventResponse",
 *     type="object",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         example=87
 *     ),
 *     @OA\Property(
 *         property="userId",
 *         type="integer",
 *         nullable=true,
 *         example=42
 *     ),
 *     @OA\Property(
 *         property="userName",
 *         type="string",
 *         nullable=true,
 *         example="Juan Pérez"
 *     ),
 *     @OA\Property(
 *         property="eventType",
 *         type="string",
 *         example="payment_confirmation"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         example="sent"
 *     ),
 *     @OA\Property(
 *         property="recipientEmail",
 *         type="string",
 *         format="email",
 *         example="juan.perez@example.com"
 *     ),
 *     @OA\Property(
 *         property="createdAt",
 *         type="string",
 *         format="date-time",
 *         example="2026-08-15 14:36:10"
 *     )
 * )
 */
class EmailEventResponse
{
    public function __construct(
        public int $id,
        public ?int $userId,
        public ?string $userName,
        public string $eventType,
        public string $status,
        public string $recipientEmail,
        public string $createdAt,
    ) {}

    public static function create(EmailEvent $event): self
    {
        return new self(
            id: $event->id,
            userId: $event->user_id,
            userName: $event->name !== null
                ? trim($event->name . ' ' . $event->last_name)
                : null,
            eventType: $event->event_type?->label(),
            status: $event->status?->label(),
            recipientEmail: $event->recipient_email,
            createdAt: $event->created_at->format('Y-m-d H:i:s'),
        );

    }

}
