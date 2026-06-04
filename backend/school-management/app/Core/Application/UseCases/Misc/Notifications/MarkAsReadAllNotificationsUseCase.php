<?php

namespace App\Core\Application\UseCases\Misc\Notifications;

use App\Core\Domain\Repositories\Command\Misc\NotificationRepInterface;
use App\Models\User;

class MarkAsReadAllNotificationsUseCase
{
    public function __construct(
        private NotificationRepInterface $notificationRep,
    ){}

    public function execute(User $user): void {
        $this->notificationRep->markAsReadAllNotifications($user);
    }

}
