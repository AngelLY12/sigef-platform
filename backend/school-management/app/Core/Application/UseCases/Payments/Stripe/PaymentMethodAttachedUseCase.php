<?php

namespace App\Core\Application\UseCases\Payments\Stripe;

use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Entities\PaymentMethod;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Domain\Enum\Cache\StudentCacheSufix;
use App\Core\Domain\Enum\Events\Types\PaymentEventType;
use App\Core\Domain\Repositories\Command\Events\PaymentEventRepInterface;
use App\Core\Domain\Repositories\Command\Payments\PaymentMethodRepInterface;
use App\Core\Domain\Repositories\Query\Events\PaymentEventQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentMethodQueryRepInterface;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Infraestructure\Cache\CacheService;
use App\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

class PaymentMethodAttachedUseCase
{
    private const TAG_CARDS = [CachePrefix::STUDENT->value, StudentCacheSufix::CARDS->value];
    public function __construct(
        private PaymentMethodRepInterface $pmRepo,
        private PaymentMethodQueryRepInterface $pmqRepo,
        private UserQueryRepInterface $userRepo,
        private CacheService $service

    ) {

    }
    public function execute(\Stripe\PaymentMethod $obj, string $eventId){

        if (!$obj) {
            return false;
        }
        $user = $this->userRepo->getUserByStripeCustomer($obj->customer);

        $paymentMethodId = $obj->id;
        $pm = $this->pmqRepo->existsPaymentMethodByStripeId($paymentMethodId);
        if ($pm) {
            return true;
        }
        try {

            $pmDomain = new PaymentMethod(
                user_id: $user->id,
                stripe_payment_method_id: $paymentMethodId,
                brand: $obj->card->brand,
                last4: $obj->card->last4,
                exp_month: $obj->card->exp_month,
                exp_year: $obj->card->exp_year,
            );
            DB::transaction(function () use ($pmDomain) {
                $this->pmRepo->create($pmDomain);

            });
            $this->service->flushTags(array_merge(self::TAG_CARDS, ["userId:{$user->id}"]));
            return true;

        } catch (\Exception $e) {
            if (!($e instanceof DomainException) && !($e instanceof \Illuminate\Validation\ValidationException)) {
                throw $e;
            }

            return false;
        }
    }

}
