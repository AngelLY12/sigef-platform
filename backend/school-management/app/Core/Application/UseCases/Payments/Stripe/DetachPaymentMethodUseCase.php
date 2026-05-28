<?php

namespace App\Core\Application\UseCases\Payments\Stripe;

use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\StudentCacheSufix;
use App\Core\Domain\Repositories\Command\Payments\PaymentMethodRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentMethodQueryRepInterface;
use App\Core\Infraestructure\Cache\CacheService;
use App\Exceptions\DomainException;

class DetachPaymentMethodUseCase
{
    public function __construct(
        private PaymentMethodRepInterface $paymentMethodRep,
        private PaymentMethodQueryRepInterface $paymentMethodQueryRep,
        private CacheService $cacheService
    ) {}

    public function execute($obj, string $eventType, string $eventId): bool
    {
        $stripePaymentMethodId = $obj->id ?? null;

        if (!$stripePaymentMethodId) {
            logger()->warning('Detach event without payment method ID', [
                'event_type' => $eventType,
                'event_id' => $eventId
            ]);
            return false;
        }
        $existingPm = $this->paymentMethodQueryRep->findByStripeId($stripePaymentMethodId);

        if (!$existingPm) {
            logger()->info('Payment method not found for detach (already removed or never existed)', [
                'stripe_id' => $stripePaymentMethodId,
                'customer_id' => $obj->customer ?? null
            ]);
            return true;
        }
        try {
            $userId = $existingPm->user_id;

            $deleted = $this->paymentMethodRep->deleteByStripeId($stripePaymentMethodId);

            if ($deleted) {
                $this->clearUserCache($userId);

                logger()->info('Payment method detached successfully', [
                    'stripe_id' => $stripePaymentMethodId,
                    'user_id' => $userId,
                    'customer_id' => $obj->customer ?? null,
                    'method_type' => $obj->type ?? 'unknown'
                ]);

                return true;
            }

            logger()->error('Failed to delete payment method', [
                'stripe_id' => $stripePaymentMethodId,
                'event_id' => $eventId
            ]);
            return false;

        } catch (\Exception $e) {
            logger()->error('Error detaching payment method', [
                'stripe_id' => $stripePaymentMethodId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

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
