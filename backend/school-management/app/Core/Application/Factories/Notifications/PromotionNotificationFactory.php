<?php

namespace App\Core\Application\Factories\Notifications;

use App\Core\Application\DTO\Response\Notifications\NotificationPayload;
use App\Core\Application\DTO\Response\Notifications\PromotionNotificationDataDTO;
use App\Core\Domain\Enum\Notification\NotificationSeverity;
use App\Core\Domain\Enum\Notification\NotificationType;

final class PromotionNotificationFactory
{
    public static function completed(
        PromotionNotificationDataDTO $data
    ): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::PROMOTION_COMPLETED,
            title: 'Promoción de estudiantes completada',
            message: "Se promovieron {$data->promoted_count} estudiantes y se dieron de baja {$data->deactivated_count}",
            severity: NotificationSeverity::SUCCESS,
            metadata: $data
        );
    }

    public static function failed(
        PromotionNotificationDataDTO $data
    ): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::PROMOTION_FAILED,
            title: 'Promoción de estudiantes no completada',
            message: "Hubo un fallo en la promoción de los estudiantes: {$data->error}",
            severity: NotificationSeverity::ERROR,
            metadata: $data
        );
    }

}
