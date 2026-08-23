<?php

namespace App\Core\Application\Services\Facades\Misc;

use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\DTO\Response\Notifications\NotificationsCountResponse;
use App\Core\Application\Traits\HasCache;
use App\Core\Application\UseCases\Misc\Notifications\CountNotificationsUseCase;
use App\Core\Application\UseCases\Misc\Notifications\DeleteNotificationUseCase;
use App\Core\Application\UseCases\Misc\Notifications\GetReadNotificationsUseCase;
use App\Core\Application\UseCases\Misc\Notifications\GetUnreadNotificationsUseCase;
use App\Core\Application\UseCases\Misc\Notifications\MarkAsReadAllNotificationsUseCase;
use App\Core\Application\UseCases\Misc\Notifications\MarkAsReadNotificationUseCase;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\NotificationsSufix;
use App\Core\Infraestructure\Cache\CacheService;
use App\Models\User;

class NotificationsServiceFacades
{
    use HasCache;
    private const TAG_READ_NOTIFICATIONS = [CachePrefix::NOTIFICATIONS->value, NotificationsSufix::READ->value];
    private const TAG_UNREAD_NOTIFICATIONS = [CachePrefix::NOTIFICATIONS->value, NotificationsSufix::UNREAD->value];
    private const TAG_COUNT_NOTIFICATIONS = [CachePrefix::NOTIFICATIONS->value, NotificationsSufix::COUNT->value];

    public function __construct(
        private CacheService $service,
        private GetReadNotificationsUseCase $readNotifications,
        private GetUnreadNotificationsUseCase $unreadNotifications,
        private MarkAsReadAllNotificationsUseCase  $markAsReadAll,
        private MarkAsReadNotificationUseCase $markAsRead,
        private DeleteNotificationUseCase $deleteNotification,
        private CountNotificationsUseCase $countNotifications,
    )
    {
        $this->setCacheService($service);
    }

    public function findReadNotifications(User $user, int $page, int $perPage, bool $forceRefresh): PaginatedResponse {
       $key = $this->generateCacheKey(CachePrefix::NOTIFICATIONS->value, NotificationsSufix::READ->value, [
           'userId' => $user->id,
           'page' => $page,
           'perPage' => $perPage,
           'forceRefresh' => $forceRefresh
       ]);
        $tags = array_merge(self::TAG_READ_NOTIFICATIONS, ["userId:{$user->id}"]);
        return $this->mediumCache($key, fn() => $this->readNotifications->execute($user, $page, $perPage), $tags, $forceRefresh);
    }

    public function findUnreadNotifications(User $user, int $page, int $perPage, bool $forceRefresh): PaginatedResponse {
        $key = $this->generateCacheKey(CachePrefix::NOTIFICATIONS->value, NotificationsSufix::UNREAD->value, [
            'userId' => $user->id,
           'page' => $page,
           'perPage' => $perPage,
           'forceRefresh' => $forceRefresh
        ]);
        $tags = array_merge(self::TAG_UNREAD_NOTIFICATIONS, ["userId:{$user->id}"]);
        return $this->mediumCache($key, fn() => $this->unreadNotifications->execute($user, $page, $perPage), $tags, $forceRefresh);
    }

    public function countNotifications(User $user, bool $forceRefresh): NotificationsCountResponse {
        $key = $this->generateCacheKey(CachePrefix::NOTIFICATIONS->value, NotificationsSufix::COUNT->value, [
            'userId' => $user->id,
            'forceRefresh' => $forceRefresh
        ]);
        $tags = array_merge(self::TAG_COUNT_NOTIFICATIONS, ["userId:{$user->id}"]);
        return $this->mediumCache($key, fn() => $this->countNotifications->execute($user), $tags, $forceRefresh);
    }

    public function markAsReadNotification(User $user, string $notificationId): void {
        $this->idempotent(
            'mark_as_read_notification',
            [
                'user_id' => $user->id,
            ],
            function () use ($user, $notificationId) {

                $this->markAsRead->execute($user, $notificationId);
                $this->service->flushTags(array_merge(self::TAG_READ_NOTIFICATIONS, ["userId:{$user->id}"]));
                $this->service->flushTags(array_merge(self::TAG_UNREAD_NOTIFICATIONS, ["userId:{$user->id}"]));
                $this->service->flushTags(array_merge(self::TAG_COUNT_NOTIFICATIONS, ["userId:{$user->id}"]));
            },
            300
        );
    }

    public function markAsReadAllNotifications(User $user): void {
        $this->idempotent(
            'mark_as_read_all_notification',
            [
                'user_id' => $user->id,
            ],
            function () use ($user) {

                $this->markAsReadAll->execute($user);
                $this->service->flushTags(array_merge(self::TAG_READ_NOTIFICATIONS, ["userId:{$user->id}"]));
                $this->service->flushTags(array_merge(self::TAG_UNREAD_NOTIFICATIONS, ["userId:{$user->id}"]));
                $this->service->flushTags(array_merge(self::TAG_COUNT_NOTIFICATIONS, ["userId:{$user->id}"]));
            },
            300
        );
    }

    public function deleteNotification(User $user, string $notificationId): void {
        $this->idempotent(
            'delete_notification',
            [
                'user_id' => $user->id,
            ],
            function () use ($user, $notificationId) {

                $this->deleteNotification->execute($user, $notificationId);
                $this->service->flushTags(array_merge(self::TAG_READ_NOTIFICATIONS, ["userId:{$user->id}"]));
                $this->service->flushTags(array_merge(self::TAG_UNREAD_NOTIFICATIONS, ["userId:{$user->id}"]));
                $this->service->flushTags(array_merge(self::TAG_COUNT_NOTIFICATIONS, ["userId:{$user->id}"]));
            },
            300
        );
    }

}
