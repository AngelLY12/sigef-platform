<?php

namespace App\Core\Infraestructure\Repositories\Query\Misc;

use App\Core\Application\DTO\Response\Notifications\NotificationsCountResponse;
use App\Core\Application\Mappers\NotificationsMapper;
use App\Core\Domain\Repositories\Query\Misc\NotificationQueryRepInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class EloquentNotificationQueryRepository implements NotificationQueryRepInterface
{

    public function findReadNotifications(User $user,int $page, int $perPage): LengthAwarePaginator
    {
        return $user->notifications()
            ->whereNotNull('read_at')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findUnreadNotifications(User $user, int $page, int $perPage): LengthAwarePaginator
    {
        return $user->unreadNotifications()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function countNotifications(User $user): NotificationsCountResponse
    {
        $unread = $user->unreadNotifications()->count();
        $read = $user->notifications()->whereNotNull('read_at')->count();
        return NotificationsMapper::toNotificationsCount($read, $unread);
    }

}
