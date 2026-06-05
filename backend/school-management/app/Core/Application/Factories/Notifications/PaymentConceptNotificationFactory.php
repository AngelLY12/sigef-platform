<?php

namespace App\Core\Application\Factories\Notifications;

use App\Core\Application\DTO\Response\Notifications\NotificationPayload;
use App\Core\Application\DTO\Response\Notifications\PaymentConceptChangedDataDTO;
use App\Core\Application\DTO\Response\Notifications\PaymentConceptStatusChangedDataDTO;
use App\Core\Domain\Enum\Notification\NotificationSeverity;
use App\Core\Domain\Enum\Notification\NotificationType;

final class PaymentConceptNotificationFactory
{
    public static function changed(
        PaymentConceptChangedDataDTO $data,
        string $title,
        string $message
    ): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::PAYMENT_CONCEPT_CHANGED,
            title: $title,
            message: $message,
            severity: NotificationSeverity::INFO,
            metadata: $data
        );
    }

    public static function statusChanged(
        PaymentConceptStatusChangedDataDTO $data,
        string $title,
        string $message
    ): NotificationPayload
    {
        return new NotificationPayload(
            type: NotificationType::PAYMENT_CONCEPT_STATUS_CHANGED,
            title: $title,
            message: $message,
            severity: NotificationSeverity::INFO,
            metadata: $data
        );
    }

}
