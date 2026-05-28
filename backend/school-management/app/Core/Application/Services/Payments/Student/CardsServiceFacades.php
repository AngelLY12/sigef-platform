<?php

namespace App\Core\Application\Services\Payments\Student;

use App\Core\Application\DTO\Response\PaymentMethod\SetupCardResponse;
use App\Core\Application\Traits\HasCache;
use App\Core\Application\UseCases\Payments\Student\Cards\DeletePaymentMethoUseCase;
use App\Core\Application\UseCases\Payments\Student\Cards\GetUserPaymentMethodsUseCase;
use App\Core\Application\UseCases\Payments\Student\Cards\SetupCardUseCase;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\StudentCacheSufix;
use App\Core\Infraestructure\Cache\CacheService;

class CardsServiceFacades
{

    use HasCache;
    private const TAG_CARDS = [CachePrefix::STUDENT->value, StudentCacheSufix::CARDS->value];

    public function __construct(
        private SetupCardUseCase $setup,
        private DeletePaymentMethoUseCase $delete,
        private GetUserPaymentMethodsUseCase $show,
        private CacheService $service
    )
    {
        $this->setCacheService($service);
    }

    public function setupCard(User $user): SetupCardResponse
    {
        return $this->idempotent(
            'stripe_setup_card',
            [
                'user_id' => $user->id,
            ],
            function () use ($user) {

                $setup = $this->setup->execute($user);
                $this->service->flushTags(array_merge(self::TAG_CARDS, ["userId:{$user->id}"]));

                return $setup;
            },
            300
        );
    }
    public function deletePaymentMethod(User $user, int $paymentMethodId): bool
    {
        return $this->idempotent(
            'stripe_delete_payment_method',
            [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethodId,
            ],
            function () use ($user, $paymentMethodId) {

                $delete = $this->delete->execute($paymentMethodId);
                $this->service->flushTags(array_merge(self::TAG_CARDS, ["userId:{$user->id}"]));

                return $delete;
            },
            300
        );
    }

    public function getUserPaymentMethods(int $userId, bool $forceRefresh): array
    {
        $key = $this->generateCacheKey(
            CachePrefix::STUDENT->value,
            StudentCacheSufix::CARDS->value,
            [
                'userId' => $userId,
            ]
        );
        $tags = array_merge(self::TAG_CARDS, ["userId:{$userId}"]);

        return $this->longCache($key,fn() =>$this->show->execute($userId), $tags,$forceRefresh );
    }
}
