<?php

namespace App\Core\Domain\Enum\Notification;

/**
 * @OA\Schema(
 *     schema="NotificationSeverity",
 *     type="string",
 *     enum={"info","success","warning","error"}
 * )
 */
enum NotificationSeverity: string
{
    case INFO = 'info';
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case ERROR = 'error';
}
