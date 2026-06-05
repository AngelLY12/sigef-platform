<?php

namespace App\Core\Application\Factories\Notifications;

use App\Core\Application\DTO\Response\Notifications\ImportNotificationDataDTO;
use App\Core\Application\DTO\Response\Notifications\NotificationPayload;
use App\Core\Domain\Enum\Notification\NotificationSeverity;
use App\Core\Domain\Enum\Notification\NotificationType;

final class ImportNotificationFactory
{

    public static function error(ImportNotificationDataDTO $data): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::IMPORT_ERROR,
            title: 'Error de importación',
            message: 'Error al terminar la importación de datos',
            severity: NotificationSeverity::ERROR,
            metadata: $data
        );
    }

    public static function finished(ImportNotificationDataDTO $data): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::IMPORT_FINISHED,
            title: 'Importación finalizada',
            message: 'Importación de datos finalizada, a continuación veras un resúmen.',
            severity: NotificationSeverity::SUCCESS,
            metadata: $data
        );
    }

}
