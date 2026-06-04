<?php

namespace App\Core\Domain\Enum\Cache;

enum NotificationsSufix: string
{
    case READ = 'read';
    case UNREAD = 'unread';
    case COUNT = 'count';
}
