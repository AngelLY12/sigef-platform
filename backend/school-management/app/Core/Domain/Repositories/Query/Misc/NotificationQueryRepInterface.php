<?php

namespace App\Core\Domain\Repositories\Query\Misc;

use App\Core\Application\DTO\Response\Notifications\NotificationsCountResponse;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface NotificationQueryRepInterface
{
    public function findReadNotifications(User $user,int $page, int $perPage): LengthAwarePaginator;
    public function findUnreadNotifications(User $user, int $page, int $perPage): LengthAwarePaginator;
    public function countNotifications(User $user): NotificationsCountResponse;
}
