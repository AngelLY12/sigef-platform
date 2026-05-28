<?php

namespace App\Core\Application\Services\Payments\Staff;

use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\DTO\Response\General\StripePayoutResponse;
use App\Core\Application\DTO\Response\Payment\FinancialSummaryResponse;
use App\Core\Application\DTO\Response\PaymentConcept\PendingSummaryResponse;
use App\Core\Application\DTO\Response\User\UsersFinancialSummary;
use App\Core\Application\Traits\HasCache;
use App\Core\Application\UseCases\Payments\Staff\Dashboard\CreatePayoutUseCase;
use App\Core\Application\UseCases\Payments\Staff\Dashboard\GetAllConceptsUseCase;
use App\Core\Application\UseCases\Payments\Staff\Dashboard\GetAllStudentsUseCase;
use App\Core\Application\UseCases\Payments\Staff\Dashboard\PaymentsMadeUseCase;
use App\Core\Application\UseCases\Payments\Staff\Dashboard\PendingPaymentAmountUseCase;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\StaffCacheSufix;
use App\Core\Infraestructure\Cache\CacheService;

class DashboardServiceFacades{
    use HasCache;
    private const TAG_DASHBOARD_PENDING = [CachePrefix::STAFF->value, StaffCacheSufix::DASHBOARD->value, "pending"];
    private const TAG_DASHBOARD_STUDENTS = [CachePrefix::STAFF->value, StaffCacheSufix::DASHBOARD->value, "students"];
    private const TAG_DASHBOARD_PAYMENTS = [CachePrefix::STAFF->value, StaffCacheSufix::DASHBOARD->value, "payments"];
    private const TAG_DASHBOARD_CONCEPTS = [CachePrefix::STAFF->value, StaffCacheSufix::DASHBOARD->value, "concepts"];
    public function __construct(
        private PendingPaymentAmountUseCase $pending,
        private GetAllStudentsUseCase $students,
        private PaymentsMadeUseCase $payments,
        private GetAllConceptsUseCase $concepts,
        private CreatePayoutUseCase $payout,
        private CacheService $service
    )
    {
        $this->setCacheService($service);
    }

    public function pendingPaymentAmount(bool $onlyThisYear, bool $forceRefresh): PendingSummaryResponse
    {
        $key = $this->generateCacheKey(
            CachePrefix::STAFF->value,
            StaffCacheSufix::DASHBOARD->value . ":pending",
            ['onlyThisYear' => $onlyThisYear]
        );

        return $this->shortCache(
            $key,
            fn() => $this->pending->execute($onlyThisYear),
            self::TAG_DASHBOARD_PENDING,
            $forceRefresh
        );
    }


    public function getAllStudents(bool $onlyThisYear, bool $forceRefresh): UsersFinancialSummary
    {
        $key = $this->generateCacheKey(
            CachePrefix::STAFF->value,
            StaffCacheSufix::DASHBOARD->value . ":students",
            ['onlyThisYear' => $onlyThisYear]
        );

        return $this->shortCache(
            $key,
            fn() => $this->students->execute($onlyThisYear),
            self::TAG_DASHBOARD_STUDENTS,
            $forceRefresh
        );
    }


    public function paymentsMade(bool $onlyThisYear, bool $forceRefresh):FinancialSummaryResponse
    {
        $key = $this->generateCacheKey(
            CachePrefix::STAFF->value,
            StaffCacheSufix::DASHBOARD->value . ":payments",
            ['onlyThisYear' => $onlyThisYear]
        );

        return $this->shortCache(
            $key,
            fn() => $this->payments->execute($onlyThisYear),
            self::TAG_DASHBOARD_PAYMENTS,
            $forceRefresh
        );
    }

    public function getAllConcepts(bool $onlyThisYear, int $perPage, int $page, bool $forceRefresh):PaginatedResponse
    {
        $key = $this->generateCacheKey(
            CachePrefix::STAFF->value,
            StaffCacheSufix::DASHBOARD->value . ":concepts",
            ['onlyThisYear' => $onlyThisYear, 'page' => $page, 'perPage' => $perPage]
        );

        return $this->shortCache(
            $key,
            fn() => $this->concepts->execute($onlyThisYear, $perPage, $page),
            self::TAG_DASHBOARD_CONCEPTS,
            $forceRefresh
        );

    }

    public function createPayout(): StripePayoutResponse
    {
        $create= $this->payout->execute();
        $this->service->flushTags(self::TAG_DASHBOARD_PAYMENTS);
        return $create;
    }

    public function refreshAll(): void
    {
        $this->service->flushTags([CachePrefix::STAFF->value,
            StaffCacheSufix::DASHBOARD->value]);
    }

}
