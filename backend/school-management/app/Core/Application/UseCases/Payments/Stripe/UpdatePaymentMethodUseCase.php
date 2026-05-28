<?php

namespace App\Core\Application\UseCases\Payments\Stripe;

use App\Core\Domain\Entities\PaymentMethod;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\StudentCacheSufix;
use App\Core\Domain\Repositories\Command\Payments\PaymentMethodRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentMethodQueryRepInterface;
use App\Core\Infraestructure\Cache\CacheService;
use App\Exceptions\DomainException;

class UpdatePaymentMethodUseCase
{
    public function __construct(
        private PaymentMethodRepInterface $paymentMethodRep,
        private PaymentMethodQueryRepInterface $paymentMethodQueryRep,
        private CacheService $cacheService
    ){}

    public function execute($obj, string $eventType, string $eventId): bool
    {
        if (!isset($obj->type) || $obj->type !== 'card') {
            logger()->info('Ignoring non-card payment method in automatic update', [
                'stripe_id' => $obj->id ?? 'unknown',
                'type' => $obj->type ?? 'unknown'
            ]);
            return true;
        }

        if (!isset($obj->card)) {
            logger()->warning('Card payment method without card data', [
                'stripe_id' => $obj->id ?? 'unknown'
            ]);
            return false;
        }
        $exists = $this->paymentMethodQueryRep->findByStripeId($obj->id);
        if (!$exists) {
            logger()->warning('Payment method not found for automatic update', [
                'stripe_id' => $obj->id,
                'customer_id' => $obj->customer ?? null
            ]);
            return false;
        }

        try {
            $fields = [
                'brand' => $obj->card->brand ?? null,
                'last4' => $obj->card->last4 ?? null,
                'exp_month' => $obj->card->exp_month ?? null,
                'exp_year' => $obj->card->exp_year ?? null,
            ];

            if (!$this->hasChanges($exists, $fields)) {
                logger()->debug('No actual changes detected in automatic update', [
                    'stripe_id' => $obj->id,
                    'event_id' => $eventId
                ]);

                return true;
            }

            $affectedRows = $this->paymentMethodRep->updateByStripeId($obj->id, $fields);

            if ($affectedRows > 0) {
                $this->clearUserCache($exists->user_id);

                logger()->info('Card automatically updated successfully', [
                    'stripe_id' => $obj->id,
                    'changes' => $fields,
                    'previous' => [
                        'brand' => $exists->brand,
                        'last4' => $exists->last4,
                        'exp_month' => $exists->exp_month,
                        'exp_year' => $exists->exp_year,
                    ]
                ]);

                return true;
            }

            logger()->error('Failed to update payment method (0 rows affected)', [
                'stripe_id' => $obj->id,
                'event_id' => $eventId
            ]);
            return false;

        }catch (\Exception $e) {
            logger()->error('Error in payment method automatic update', [
                'stripe_id' => $obj->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (!($e instanceof DomainException) && !($e instanceof \Illuminate\Validation\ValidationException)) {
                throw $e;
            }

            return false;
        }

    }

    private function hasChanges(PaymentMethod $existingPm, array $newFields): bool
    {
        return $existingPm->brand != $newFields['brand'] ||
            $existingPm->last4 != $newFields['last4'] ||
            $existingPm->exp_month != $newFields['exp_month'] ||
            $existingPm->exp_year != $newFields['exp_year'];
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
