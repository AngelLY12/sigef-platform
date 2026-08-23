<?php

namespace App\Core\Application\Services\Facades\Admin;

use App\Core\Application\DTO\Request\Events\Reconciliation\ReconciliationEventFilters;
use App\Core\Application\DTO\Response\Events\ReconcileEvent\ReconcileEventByIdResponse;
use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\Traits\HasCache;
use App\Core\Application\UseCases\Admin\Events\ReconcileEvents\GetPaymentReconciliationTimelineUseCase;
use App\Core\Application\UseCases\Admin\Events\ReconcileEvents\GetReconciliationEventsUseCase;
use App\Core\Application\UseCases\Admin\Events\ReconcileEvents\GetReconciliatonEventByIdUseCase;
use App\Core\Domain\Enum\Cache\AdminCacheSufix;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Utils\Helpers\ArrayHelper;
use App\Core\Infraestructure\Cache\CacheService;
use Illuminate\Support\Collection;

class ReconcileEventsServiceFacades
{
    use HasCache;
    private const TAG_ALL_RECONCILE_EVENTS = [CachePrefix::ADMIN->value, AdminCacheSufix::RECONCILE_EVENTS->value, 'all'];
    private const TAG_ALL_RECONCILE_EVENTS_TIMELINE = [CachePrefix::ADMIN->value, AdminCacheSufix::RECONCILE_EVENTS->value, 'timeline:id'];
    private const TAG_RECONCILE_EVENT_BY_ID = [CachePrefix::ADMIN->value, AdminCacheSufix::RECONCILE_EVENTS->value, 'by_id'];
    public function __construct(
        private GetReconciliationEventsUseCase $allReconciliationEventsUseCase,
        private GetPaymentReconciliationTimelineUseCase $timelineUseCase,
        private GetReconciliatonEventByIdUseCase $reconciliationEventByIdUseCase,
        private CacheService $service
    )
    {
        $this->setCacheService($service);
    }

    public function getAllReconciliationEvents(ReconciliationEventFilters $filters, bool $forceRefresh): PaginatedResponse
    {
        $params = ArrayHelper::filterNullValues($filters->toArray());
        $key = $this->generateCacheKey(
            CachePrefix::ADMIN->value,
            AdminCacheSufix::RECONCILE_EVENTS->value . ':all',
            $params
        );
        return $this->shortCache(key: $key, callback: fn () => $this->allReconciliationEventsUseCase->execute($filters),tags: self::TAG_ALL_RECONCILE_EVENTS,forceRefresh: $forceRefresh);
    }

    public function getReconcileEventsTimeline(int $paymentId, bool $forceRefresh): Collection
    {
        $key = $this->generateCacheKey(
            CachePrefix::ADMIN->value,
            AdminCacheSufix::RECONCILE_EVENTS->value . ':timeline:id',
            [
                'paymentId' => $paymentId,
            ]
        );
        $tags = ArrayHelper::mergeValues(self::TAG_ALL_RECONCILE_EVENTS_TIMELINE, ["paymentId:{$paymentId}"]);
        return $this->mediumCache(key: $key, callback: fn () => $this->timelineUseCase->execute($paymentId),tags: $tags,forceRefresh: $forceRefresh);
    }

    public function getReconcilitionEventById(int $id, bool $forceRefresh): ReconcileEventByIdResponse
    {
        $key = $this->generateCacheKey(
            CachePrefix::ADMIN->value,
            AdminCacheSufix::RECONCILE_EVENTS->value . ':by_id',
            [
                'reconcileId' => $id,
            ]
        );
        $tags = ArrayHelper::mergeValues(self::TAG_RECONCILE_EVENT_BY_ID, ["reconcileId:{$id}"]);
        return $this->longCache(key: $key,callback: fn() => $this->reconciliationEventByIdUseCase->execute($id),tags: $tags,forceRefresh: $forceRefresh);
    }

}
