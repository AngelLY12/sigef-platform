<?php

namespace App\Core\Application\Factories\Notifications;

use App\Core\Application\DTO\Response\Notifications\InvitationNotificationDataDTO;
use App\Core\Application\DTO\Response\Notifications\NotificationPayload;
use App\Core\Domain\Enum\Notification\NotificationSeverity;
use App\Core\Domain\Enum\Notification\NotificationType;

final class InvitationNotificationFactory
{
    public static function accepted(InvitationNotificationDataDTO $data): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::INVITATION_ACCEPTED,
            title: 'Nuevo familiar agregado',
            message: "Hola {$data->student_name}, tu familiar {$data->parent_name} aceptó tu invitación",
            severity: NotificationSeverity::SUCCESS,
            metadata: $data
        );
    }

    public static function failed(InvitationNotificationDataDTO $data): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::INVITATION_FAILED,
            title: 'Fallo al aceptar invitación',
            message: "Hola {$data->student_name}, hubo un fallo inesperado en la invitación enviada a tu familiar {$data->parent_name}. Puedes iniciar el proceso de nuevo enviando una nueva invitación.",
            severity: NotificationSeverity::ERROR,
            metadata: $data
        );
    }

}
