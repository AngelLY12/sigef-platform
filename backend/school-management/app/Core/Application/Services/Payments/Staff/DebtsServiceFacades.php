<?php

namespace App\Core\Application\Services\Payments\Staff;

use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\DTO\Response\Payment\PaymentValidateResponse;
use App\Core\Application\Traits\HasCache;
use App\Core\Application\UseCases\Payments\Staff\Debts\GetPaymentsFromStripeUseCase;
use App\Core\Application\UseCases\Payments\Staff\Debts\ShowAllPendingPaymentsUseCase;
use App\Core\Application\UseCases\Payments\Staff\Debts\ValidatePaymentUseCase;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\StaffCacheSufix;
use App\Core\Infraestructure\Cache\CacheService;

class DebtsServiceFacades{
    use HasCache;
    private const TAG_DEBTS_PENDING = [CachePrefix::STAFF->value, StaffCacheSufix::DEBTS->value, "pending"];
    private const TAG_DEBTS_STRIPE = [CachePrefix::STAFF->value, StaffCacheSufix::DEBTS->value, "stripe"];
    public function __construct(
        private ShowAllPendingPaymentsUseCase $pending,
        private ValidatePaymentUseCase $validate,
        private GetPaymentsFromStripeUseCase $payments,
        private CacheService $service

    )
    {
        $this->setCacheService($service);

    }
    public function showAllpendingPayments(?string $search, int $perPage, int $page, bool $forceRefresh): PaginatedResponse
    {
        $key = $this->generateCacheKey(
            CachePrefix::STAFF->value,
            StaffCacheSufix::DEBTS->value . ":pending",
            [
                'search' => $search,
                'perPage' => $perPage,
                'page' => $page
            ]
        );

        return $this->shortCache(
            $key,
            fn() => $this->pending->execute($search, $perPage, $page),
            self::TAG_DEBTS_PENDING,
            $forceRefresh
        );
    }

    public function validatePayment(string $search, string $payment_intent_id): PaymentValidateResponse
    {
        return $this->idempotent(
            'stripe_validate_payment',
            [
                'payment_intent_id' => $payment_intent_id,
            ],
            function () use ($search, $payment_intent_id) {

                $validate = $this->validate->execute($search, $payment_intent_id);

                $this->service->flushTags(self::TAG_DEBTS_PENDING);

                return $validate;
            },
            300
        );
    }

    public function getPaymentsFromStripe(string $search, ?int $year, bool $forceRefresh):array
    {
        $key = $this->generateCacheKey(
            CachePrefix::STAFF->value,
            StaffCacheSufix::DEBTS->value . ":stripe",
            [
                'search' => $search,
                'year' => $year ?? now()->year,
            ]
        );
        return $this->shortCache($key, fn() => $this->payments->execute($search,$year), self::TAG_DEBTS_STRIPE, $forceRefresh);
    }

}
