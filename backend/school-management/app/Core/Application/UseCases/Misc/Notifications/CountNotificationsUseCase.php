<?php

namespace App\Core\Application\UseCases\Misc\Notifications;

use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\DTO\Response\Notifications\NotificationsCountResponse;
use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Domain\Repositories\Query\Misc\NotificationQueryRepInterface;
use App\Models\User;

class CountNotificationsUseCase
{
    public function __construct(
        private NotificationQueryRepInterface $notificationQueryRep,
    ){}

    public function execute(User $user): NotificationsCountResponse {
       return $this->notificationQueryRep->countNotifications($user);
    }

}
