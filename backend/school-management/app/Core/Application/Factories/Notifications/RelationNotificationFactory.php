<?php

namespace App\Core\Application\Factories\Notifications;

use App\Core\Application\DTO\Response\Notifications\NotificationPayload;
use App\Core\Application\DTO\Response\Notifications\RelationNotificationDataDTO;
use App\Core\Domain\Enum\Notification\NotificationSeverity;
use App\Core\Domain\Enum\Notification\NotificationType;

final class RelationNotificationFactory
{
    public static function deleted(RelationNotificationDataDTO $data): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::RELATION_DELETED,
            title: 'Relación eliminada',
            message: "Hola {$data->parent_name}, se eliminó la relación con tu familiar {$data->student_name}.",
            severity: NotificationSeverity::WARNING,
            metadata: $data
        );
    }

}
