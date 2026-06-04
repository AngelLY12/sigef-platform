<?php

namespace App\Core\Application\UseCases\Misc\Notifications;

use App\Core\Domain\Repositories\Command\Misc\NotificationRepInterface;
use App\Models\User;

class MarkAsReadNotificationUseCase
{
    public function __construct(
        private NotificationRepInterface $notificationRep,
    ){}

    public function execute(User $user,string $notificationId): void {
        $this->notificationRep->markAsReadNotification($user,$notificationId);
    }

}
