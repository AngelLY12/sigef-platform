<?php
namespace App\Core\Application\Services\Payments\Student;

use App\Core\Application\DTO\Response\General\PaginatedResponse;
use App\Core\Application\DTO\Response\Payment\PaymentToDisplay;
use App\Core\Application\Traits\HasCache;
use App\Core\Application\UseCases\Payments\Student\PaymentHistory\FindPaymentByIdUseCase;
use App\Core\Application\UseCases\Payments\Student\PaymentHistory\GenerateReceiptFromPaymentUseCase;
use App\Core\Application\UseCases\Payments\Student\PaymentHistory\GetPaymentHistoryUseCase;
use App\Core\Application\UseCases\Payments\Student\PaymentHistory\GetValidatedReceiptUseCase;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\StudentCacheSufix;
use App\Core\Infraestructure\Cache\CacheService;
use App\Models\Receipt;

class PaymentHistoryService {
    use HasCache;
    private const TAG_PAYMENTS_HISTORY =[CachePrefix::STUDENT->value, StudentCacheSufix::HISTORY->value];
    public function __construct(
        private GetPaymentHistoryUseCase $history,
        private FindPaymentByIdUseCase $payment,
        private GenerateReceiptFromPaymentUseCase $generateReceipt,
        private GetValidatedReceiptUseCase $validated,
        private CacheService $service
    ) {
        $this->setCacheService($service);
    }

    public function paymentHistory(User $user, int $perPage, int $page, bool $forceRefresh): PaginatedResponse {
        $key = $this->generateCacheKey(
            CachePrefix::STUDENT->value,
            StudentCacheSufix::HISTORY->value,
            [
                'userId' => $user->id,
                'perPage' => $perPage,
                'page' => $page,
            ]
        );
        $tags = array_merge(self::TAG_PAYMENTS_HISTORY, ["userId:{$user->id}"]);
        return $this->mediumCache($key,fn() => $this->history->execute($user->id, $perPage, $page),$tags,$forceRefresh);
    }

    public function findPayment(int $id, bool $forceRefresh): PaymentToDisplay
    {
        $key = $this->generateCacheKey(
            CachePrefix::STUDENT->value,
            StudentCacheSufix::HISTORY->value,
            [
                'paymentId' => $id,
            ]
        );
        $tags = array_merge(self::TAG_PAYMENTS_HISTORY, ["paymentId:$id"]);
        return $this->mediumCache($key, fn() => $this->payment->execute($id),$tags,$forceRefresh);
    }

    public function receiptFromPayment(int $paymentId): string
    {
        return $this->idempotent(
            'create_or_get_receipt',
            [
                'paymentId' => $paymentId,
            ],
            function () use ($paymentId) {
                return $this->generateReceipt->execute($paymentId);
            }
        );
    }

    public function validateReceipt(string $folio): Receipt
    {
        $key = $this->generateCacheKey(
            CachePrefix::STUDENT->value,
            StudentCacheSufix::HISTORY->value,
            [
                'folio' => $folio,
            ]
        );
        $tags = array_merge(self::TAG_PAYMENTS_HISTORY, ["folio:$folio"]);
        return $this->shortCache($key, fn() => $this->validated->execute($folio), $tags, false);
    }

}
