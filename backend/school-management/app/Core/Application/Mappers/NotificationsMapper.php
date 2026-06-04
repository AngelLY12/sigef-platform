<?php

namespace App\Core\Application\Mappers;

use App\Core\Application\DTO\Response\Notifications\NotificationsCountResponse;

class NotificationsMapper
{
    public static function toNotificationsCount(int $read, int $unread): NotificationsCountResponse
    {
        return new NotificationsCountResponse(
            read_count: $read,
            unread_count: $unread
        );
    }

}
