<?php

namespace App\Core\Domain\Repositories\Command\Misc;

use App\Models\User;

interface NotificationRepInterface
{
    public function markAsReadNotification(User $user,string $notificationId): void;
    public function markAsReadAllNotifications(User $user): void;
    public function deleteNotifications(User $user,string $notificationId): void;

}
