<?php

namespace App\Core\Application\UseCases\Misc\Notifications;

use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Domain\Repositories\Query\Misc\NotificationQueryRepInterface;
use App\Models\User;
use Illuminate\Support\Collection;

class GetUnreadNotificationsUseCase
{
    public function __construct(
        private NotificationQueryRepInterface $notificationQueryRep,
    ){}

    public function execute(User $user, int $page, int $perPage): PaginatedResponse {
        $paginated = $this->notificationQueryRep->findUnreadNotifications($user, $page, $perPage);
        return GeneralMapper::toPaginatedResponse($paginated->items(), $paginated);
    }

}
