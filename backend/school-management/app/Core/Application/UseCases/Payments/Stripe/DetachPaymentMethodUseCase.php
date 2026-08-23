<?php

namespace App\Core\Application\UseCases\Payments\Stripe;

use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\StudentCacheSufix;
use App\Core\Domain\Repositories\Command\Payments\PaymentMethodRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentMethodQueryRepInterface;
use App\Core\Infraestructure\Cache\CacheService;
use App\Exceptions\DomainException;
use Stripe\PaymentMethod;

class DetachPaymentMethodUseCase
{
    public function __construct(
        private PaymentMethodRepInterface $paymentMethodRep,
        private PaymentMethodQueryRepInterface $paymentMethodQueryRep,
        private CacheService $cacheService
    ) {}

    public function execute(PaymentMethod $obj, string $eventType, string $eventId): bool
    {
        $stripePaymentMethodId = $obj->id ?? null;

        if (!$stripePaymentMethodId) {
            return false;
        }
        $existingPm = $this->paymentMethodQueryRep->findByStripeId($stripePaymentMethodId);

        if (!$existingPm) {
            return true;
        }
        try {
            $userId = $existingPm->user_id;

            $deleted = $this->paymentMethodRep->deleteByStripeId($stripePaymentMethodId);

            if ($deleted) {
                $this->clearUserCache($userId);

                return true;
            }
            return false;

        } catch (\Exception $e) {

            if (!($e instanceof DomainException) && !($e instanceof \Illuminate\Validation\ValidationException)) {
                throw $e;
            }

            return false;
        }

    }

    private function clearUserCache(int $userId): void
    {
        $tags = array_merge(
            [CachePrefix::STUDENT->value, StudentCacheSufix::CARDS->value],
            ["userId:{$userId}"]
        );

        $this->cacheService->flushTags($tags);
    }

}
