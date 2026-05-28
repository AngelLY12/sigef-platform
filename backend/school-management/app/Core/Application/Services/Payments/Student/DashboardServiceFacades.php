<?php
namespace App\Core\Application\Services\Payments\Student;

use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\DTO\Response\Payment\PaymentsSummaryResponse;
use App\Core\Application\DTO\Response\PaymentConcept\PendingSummaryResponse;
use App\Core\Application\Traits\HasCache;
use App\Core\Application\UseCases\Payments\Student\Dashboard\OverduePaymentsUseCase;
use App\Core\Application\UseCases\Payments\Student\Dashboard\PaymentHistoryUseCase;
use App\Core\Application\UseCases\Payments\Student\Dashboard\PaymentsMadeUseCase;
use App\Core\Application\UseCases\Payments\Student\Dashboard\PendingPaymentAmountUseCase;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\StudentCacheSufix;
use App\Core\Infraestructure\Cache\CacheService;

class DashboardServiceFacades {
    use HasCache;
    private const TAG_MAIN =[CachePrefix::STUDENT->value, StudentCacheSufix::DASHBOARD_USER->value];
    private const TAG_DASHBOARD_USER_PENDING = [CachePrefix::STUDENT->value, StudentCacheSufix::DASHBOARD_USER->value, "pending"];
    private const TAG_DASHBOARD_USER_PAYMENTS = [CachePrefix::STUDENT->value, StudentCacheSufix::DASHBOARD_USER->value, "payments"];
    private const TAG_DASHBOARD_USER_OVERDUE = [CachePrefix::STUDENT->value, StudentCacheSufix::DASHBOARD_USER->value, "overdue"];
    private const TAG_DASHBOARD_USER_HISTORY = [CachePrefix::STUDENT->value, StudentCacheSufix::DASHBOARD_USER->value, "history"];

    public function __construct(
        private PendingPaymentAmountUseCase $pending,
        private PaymentsMadeUseCase $payments,
        private OverduePaymentsUseCase $overdue,
        private PaymentHistoryUseCase $history,
        private CacheService $service
    ) {
        $this->setCacheService($service);

    }


    public function pendingPaymentAmount(bool $onlyThisYear, User $user, bool $forceRefresh): PendingSummaryResponse {
        $key = $this->generateCacheKey(
            CachePrefix::STUDENT->value,
            StudentCacheSufix::DASHBOARD_USER->value . ":pending",
            [
                'userId' => $user->id,
                'onlyThisYear' => $onlyThisYear,
            ]
        );
        $tags = array_merge(self::TAG_DASHBOARD_USER_PENDING,["userId:{$user->id}"]);
        return $this->shortCache($key,fn() => $this->pending->execute($user, $onlyThisYear),$tags,$forceRefresh );
    }

    public function paymentsMade(bool $onlyThisYear, User $user, bool $forceRefresh): PaymentsSummaryResponse {
        $key = $this->generateCacheKey(
            CachePrefix::STUDENT->value,
            StudentCacheSufix::DASHBOARD_USER->value . ":payments",
            [
                'userId' => $user->id,
                'onlyThisYear' => $onlyThisYear,
            ]
        );
        $tags = array_merge(self::TAG_DASHBOARD_USER_PAYMENTS,["userId:{$user->id}"]);
        return $this->shortCache($key ,fn() => $this->payments->execute($user->id, $onlyThisYear),$tags,$forceRefresh);
    }

    public function overduePayments(bool $onlyThisYear, User $user, bool $forceRefresh): PendingSummaryResponse {
        $key = $this->generateCacheKey(
            CachePrefix::STUDENT->value,
            StudentCacheSufix::DASHBOARD_USER->value . ":overdue",
            [
                'userId' => $user->id,
                'onlyThisYear' => $onlyThisYear,
            ]
        );
        $tags = array_merge(self::TAG_DASHBOARD_USER_OVERDUE,["userId:{$user->id}"]);
        return $this->shortCache($key ,fn() => $this->overdue->execute($user, $onlyThisYear),$tags,$forceRefresh);
    }

    public function paymentHistory(bool $onlyThisYear, User $user, int $perPage, int $page, bool $forceRefresh): PaginatedResponse {
        $key = $this->generateCacheKey(
            CachePrefix::STUDENT->value,
            StudentCacheSufix::DASHBOARD_USER->value . ":history",
            [
                'userId' => $user->id,
                'onlyThisYear' => $onlyThisYear,
            ]
        );
        $tags = array_merge(self::TAG_DASHBOARD_USER_HISTORY,["userId:{$user->id}"]);
        return $this->shortCache($key ,fn() => $this->history->execute($user->id, $perPage, $page, $onlyThisYear),$tags,$forceRefresh);

    }

    public function refreshAll(int $userId): void
    {
        $this->service->flushTags(array_merge(self::TAG_MAIN,["userId:{$userId}"]));
    }
}
