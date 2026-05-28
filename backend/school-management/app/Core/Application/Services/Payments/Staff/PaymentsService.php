<?php

namespace App\Core\Application\Services\Payments\Staff;

use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\Traits\HasCache;
use App\Core\Application\UseCases\Payments\Staff\Payments\ShowAllPaymentsByConceptNameUseCase;
use App\Core\Application\UseCases\Payments\Staff\Payments\ShowAllPaymentUseCase;
use App\Core\Application\UseCases\Payments\Staff\Payments\ShowAllStudentPaymentsUseCase;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\StaffCacheSufix;
use App\Core\Infraestructure\Cache\CacheService;

class PaymentsService{
    use HasCache;
    private const TAG_PAYMENTS = [CachePrefix::STAFF->value, StaffCacheSufix::PAYMENTS->value, "show"];
    private const TAG_PAYMENTS_BY_CONCEPT = [CachePrefix::STAFF->value, StaffCacheSufix::PAYMENTS->value, "show", "by-concept"];
    private const TAG_STUDENT_PAYMENTS = [CachePrefix::STAFF->value, StaffCacheSufix::PAYMENTS->value, "show", "students"];

    public function __construct(
        private ShowAllPaymentUseCase $payments,
        private ShowAllPaymentsByConceptNameUseCase $paymentsByConceptName,
        private ShowAllStudentPaymentsUseCase $studentPayments,

        private CacheService $service
    )
    {
        $this->setCacheService($service);
    }
    public function showAllPayments(?string $search, int $perPage, int $page,  bool $forceRefresh): PaginatedResponse
    {
        $key = $this->generateCacheKey(
            CachePrefix::STAFF->value,
            StaffCacheSufix::PAYMENTS->value . ":show",
            [
                'search' => $search,
                'perPage' => $perPage,
                'page' => $page
            ]
        );
        return $this->mediumCache($key,fn() =>$this->payments->execute($search,$perPage, $page), self::TAG_PAYMENTS ,$forceRefresh);
    }

    public function showAllPaymentsByConceptName(?string $search, int $perPage, int $page,  bool $forceRefresh): PaginatedResponse
    {
        $key = $this->generateCacheKey(
            CachePrefix::STAFF->value,
            StaffCacheSufix::PAYMENTS->value . ":show:by-concept",
            [
                'search' => $search,
                'perPage' => $perPage,
                'page' => $page
            ]
        );
        return $this->mediumCache($key,fn() =>$this->paymentsByConceptName->execute($search,$perPage, $page),self::TAG_PAYMENTS_BY_CONCEPT,$forceRefresh);
    }

    public function showAllStudents(?string $search, int $perPage, int $page, bool $forceRefresh):PaginatedResponse
    {
        $key = $this->generateCacheKey(
            CachePrefix::STAFF->value,
            StaffCacheSufix::PAYMENTS->value . ":show:students",
            [
                'search' => $search,
                'perPage' => $perPage,
                'page' => $page
            ]
        );
        return $this->mediumCache($key,fn() =>$this->studentPayments->execute($search,$perPage, $page),self::TAG_STUDENT_PAYMENTS,$forceRefresh);
    }

}
