<?php

namespace App\Core\Application\DTO\Response\Notifications;

/**
 * @OA\Schema(
 *     schema="NotificationsCountResponse",
 *     type="object",
 *
 *     @OA\Property(
 *         property="read_count",
 *         type="integer",
 *         example=12
 *     ),
 *
 *     @OA\Property(
 *         property="unread_count",
 *         type="integer",
 *         example=3
 *     )
 * )
 */
class NotificationsCountResponse
{
    public function __construct(
        public int $read_count,
        public int $unread_count,
    ){}

}
