<?php

namespace App\Core\Domain\Enum\Notification;

enum NotificationConceptPriority: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';

}
