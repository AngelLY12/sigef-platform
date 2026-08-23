<?php

namespace App\Core\Application\Services\Facades\Admin;

use App\Core\Application\DTO\Request\Events\EmailEvent\EmailEventFilters;
use App\Core\Application\DTO\Request\Events\EmailEvent\EmailEventHistoryFilters;
use App\Core\Application\DTO\Response\Events\EmailEvent\EmailEventByIdResponse;
use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\Traits\HasCache;
use App\Core\Application\UseCases\Admin\Events\EmailEvents\GetEmailEventByIdUseCase;
use App\Core\Application\UseCases\Admin\Events\EmailEvents\GetEmailEventsUseCase;
use App\Core\Application\UseCases\Admin\Events\EmailEvents\GetUserEmailHistoryUseCase;
use App\Core\Domain\Enum\Cache\AdminCacheSufix;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Utils\Helpers\ArrayHelper;
use App\Core\Infraestructure\Cache\CacheService;

class EmailEventsServiceFacades
{
    use HasCache;
    private const TAG_ALL_EMAIL_EVENTS = [CachePrefix::ADMIN->value, AdminCacheSufix::EMAIL_EVENTS->value, 'all'];
    private const TAG_ALL_EMAIL_EVENTS_TIMELINE = [CachePrefix::ADMIN->value, AdminCacheSufix::EMAIL_EVENTS->value, 'timeline'];
    private const TAG_EMAIL_EVENT_BY_ID = [CachePrefix::ADMIN->value, AdminCacheSufix::EMAIL_EVENTS->value, 'by_id'];
    public function __construct(
        private GetEmailEventsUseCase      $getEmailEventsUseCase,
        private GetUserEmailHistoryUseCase $getUserEmailTimelineUseCase,
        private GetEmailEventByIdUseCase $getEmailEventByIdUseCase,
        private CacheService               $service,
    ){
        $this->setCacheService($service);
    }

    public function getAllEmailEvents(EmailEventFilters $filters, bool $forceRefresh): PaginatedResponse
    {
        $params = ArrayHelper::filterNullValues($filters->toArray());
        $key = $this->generateCacheKey(
            CachePrefix::ADMIN->value,
            AdminCacheSufix::EMAIL_EVENTS->value . ':all',
            $params
        );
        return $this->shortCache(key: $key, callback: fn () => $this->getEmailEventsUseCase->execute($filters),tags: self::TAG_ALL_EMAIL_EVENTS,forceRefresh: $forceRefresh);
    }

    public function getEmailEventsHistory(EmailEventHistoryFilters $filters, int $userId, bool $forceRefresh): PaginatedResponse
    {
        $params = ArrayHelper::filterNullValues($filters->toArray());
        $params = ArrayHelper::mergeValues(['userId' => $userId], $params);
        $key = $this->generateCacheKey(
            CachePrefix::ADMIN->value,
            AdminCacheSufix::EMAIL_EVENTS->value . ':timeline',
            $params
        );
        $tags = ArrayHelper::mergeValues(self::TAG_ALL_EMAIL_EVENTS_TIMELINE, ["userId:{$userId}"]);
        return $this->mediumCache(key: $key, callback: fn () => $this->getUserEmailTimelineUseCase->execute($filters,$userId),tags: $tags,forceRefresh: $forceRefresh);
    }

    public function getEmailEventById(int $emailEventId, bool $forceRefresh): EmailEventByIdResponse
    {
        $key = $this->generateCacheKey(
            CachePrefix::ADMIN->value,
            AdminCacheSufix::EMAIL_EVENTS->value . ':by_id',
            [
                'emailEventId' => $emailEventId,
            ]
        );
        $tags = ArrayHelper::mergeValues(self::TAG_EMAIL_EVENT_BY_ID, ["emailEventId:{$emailEventId}"]);
        return $this->longCache(key: $key,callback: fn () => $this->getEmailEventByIdUseCase->execute($emailEventId),tags: $tags,forceRefresh: $forceRefresh);

    }

}
