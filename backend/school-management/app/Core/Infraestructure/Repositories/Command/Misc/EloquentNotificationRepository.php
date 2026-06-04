<?php

namespace App\Core\Infraestructure\Repositories\Command\Misc;

use App\Core\Domain\Repositories\Command\Misc\NotificationRepInterface;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class EloquentNotificationRepository implements NotificationRepInterface
{
    public function markAsReadNotification(User $user,string $notificationId): void
    {
        $notification = $this->findNotification($user, $notificationId);
        $notification->markAsRead();
    }

    public function deleteNotifications(User $user,string $notificationId): void
    {
        $notification = $this->findNotification($user, $notificationId);
        $notification->delete();
    }

    public function markAsReadAllNotifications(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    private function findNotification(
        User $user,
        string $notificationId
    ): DatabaseNotification
    {
        return $user->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();
    }


}
