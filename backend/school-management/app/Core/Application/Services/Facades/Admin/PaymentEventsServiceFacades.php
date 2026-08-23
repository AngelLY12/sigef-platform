<?php

namespace App\Core\Application\Services\Facades\Admin;

use App\Core\Application\DTO\Request\Events\PaymentEvent\PaymentEventFilters;
use App\Core\Application\DTO\Response\Events\PaymentEvent\PaymentEventByIdResponse;
use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\Traits\HasCache;
use App\Core\Application\UseCases\Admin\Events\PaymentEvents\GetPaymentEventByIdUseCase;
use App\Core\Application\UseCases\Admin\Events\PaymentEvents\GetPaymentEventsUseCase;
use App\Core\Application\UseCases\Admin\Events\PaymentEvents\GetPaymentTimelineUseCase;
use App\Core\Domain\Enum\Cache\AdminCacheSufix;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Utils\Helpers\ArrayHelper;
use App\Core\Infraestructure\Cache\CacheService;
use Illuminate\Support\Collection;

class PaymentEventsServiceFacades
{
    use HasCache;
    private const TAG_ALL_PAYMENT_EVENTS = [CachePrefix::ADMIN->value, AdminCacheSufix::PAYMENT_EVENTS->value, 'all'];
    private const TAG_ALL_PAYMENT_EVENTS_TIMELINE = [CachePrefix::ADMIN->value, AdminCacheSufix::PAYMENT_EVENTS->value, 'timeline:id'];
    private const TAG_PAYMENT_EVENT_BY_ID = [CachePrefix::ADMIN->value, AdminCacheSufix::PAYMENT_EVENTS->value, 'by_id'];
    public function __construct(
        private GetPaymentEventsUseCase $getPaymentEventsUseCase,
        private GetPaymentTimelineUseCase $getPaymentTimelineUseCase,
        private GetPaymentEventByIdUseCase $getPaymentEventByIdUseCase,
        private CacheService $service
    ){
        $this->setCacheService($service);
    }

    public function getAllPaymentEvents(PaymentEventFilters $filters, bool $forceRefresh): PaginatedResponse
    {
        $params = ArrayHelper::filterNullValues($filters->toArray());
        $key = $this->generateCacheKey(
            CachePrefix::ADMIN->value,
            AdminCacheSufix::PAYMENT_EVENTS->value . ':all',
            $params
        );
        return $this->shortCache(key: $key, callback: fn () => $this->getPaymentEventsUseCase->execute($filters),tags: self::TAG_ALL_PAYMENT_EVENTS,forceRefresh: $forceRefresh);
    }

    public function getPaymentEventsTimeline(int $paymentId, bool $forceRefresh): Collection
    {
        $key = $this->generateCacheKey(
            CachePrefix::ADMIN->value,
            AdminCacheSufix::PAYMENT_EVENTS->value . ':timeline:id',
            [
                'paymentId' => $paymentId
            ]
        );
        $tags = ArrayHelper::mergeValues(self::TAG_ALL_PAYMENT_EVENTS_TIMELINE, ["paymentId:{$paymentId}"]);
        return $this->mediumCache(key: $key, callback: fn () => $this->getPaymentTimelineUseCase->execute($paymentId),tags: $tags,forceRefresh: $forceRefresh);
    }

    public function getPaymentEventById(int $paymentEventId, bool $forceRefresh): PaymentEventByIdResponse
    {
        $key = $this->generateCacheKey(
            CachePrefix::ADMIN->value,
            AdminCacheSufix::PAYMENT_EVENTS->value . ':by_id',
            [
                'paymentEventId' => $paymentEventId
            ]
        );
        $tags = ArrayHelper::mergeValues(self::TAG_PAYMENT_EVENT_BY_ID, ["paymentEventId:{$paymentEventId}"]);
        return $this->longCache(key: $key, callback: fn () => $this->getPaymentEventByIdUseCase->execute($paymentEventId),tags: $tags,forceRefresh: $forceRefresh);

    }

}
