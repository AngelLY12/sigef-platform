<?php

namespace App\Core\Application\DTO\Response\Notifications;

class NotificationsCountResponse
{
    public function __construct(
        public int $read_count,
        public int $unread_count,
    ){}

}
