<?php

namespace App\Core\Application\Services\Payments\Student;

use App\Core\Application\Traits\HasCache;
use App\Core\Application\UseCases\Payments\Student\PendingPayment\PayConceptUseCase;
use App\Core\Application\UseCases\Payments\Student\PendingPayment\ShowOverduePaymentsUseCase;
use App\Core\Application\UseCases\Payments\Student\PendingPayment\ShowPendingPaymentsUseCase;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\StudentCacheSufix;
use App\Core\Infraestructure\Cache\CacheService;

class PendingPaymentServiceFacades{
    use HasCache;
    private const TAG_PENDING_CONCEPTS =[CachePrefix::STUDENT->value, StudentCacheSufix::PENDING->value];

    public function __construct(
        private ShowPendingPaymentsUseCase $pending,
        private ShowOverduePaymentsUseCase $overdue,
        private PayConceptUseCase $pay,
        private CacheService $service,
    ) {
        $this->setCacheService($service);
    }

    public function showPendingPayments(User $user, bool $forceRefresh): array {
        $key = $this->generateCacheKey(
            CachePrefix::STUDENT->value,
            StudentCacheSufix::PENDING->value . ":pending",
            [
                'userId' => $user->id,
            ]
        );
        $tags = array_merge(self::TAG_PENDING_CONCEPTS, ["userId:{$user->id}"]);
        return $this->shortCache($key,fn() =>  $this->pending->execute($user),$tags,$forceRefresh );
    }

    public function showOverduePayments(User $user, bool $forceRefresh): array {
        $key = $this->generateCacheKey(
            CachePrefix::STUDENT->value,
            StudentCacheSufix::PENDING->value . ":overdue",
            [
                'userId' => $user->id,
            ]
        );
        $tags = array_merge(self::TAG_PENDING_CONCEPTS, ["overdue","userId:{$user->id}"]);
        return $this->shortCache($key,fn() => $this->overdue->execute($user),$tags,$forceRefresh);
    }

    public function payConcept(User $user, int $conceptId): string {
        return $this->idempotent(
            'stripe_pay_concept',
            [
                'user_id' => $user->id,
                'concept_id' => $conceptId,
            ],
            function () use ($user, $conceptId) {

                $pay = $this->pay->execute($user, $conceptId);
                $this->service->flushTags(array_merge(self::TAG_PENDING_CONCEPTS, ["userId:{$user->id}"]));

                return $pay;
            },
            900
        );
    }

}
