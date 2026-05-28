<?php

namespace App\Core\Application\UseCases\Payments\Student\Cards;

use App\Core\Application\DTO\Response\PaymentMethod\SetupCardResponse;
use App\Core\Application\Mappers\PaymentMethodMapper;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Repositories\Command\User\UserRepInterface;
use App\Core\Domain\Repositories\Stripe\StripeGatewayInterface;
use App\Exceptions\Validation\ValidationException;

class SetupCardUseCase
{
 public function __construct(
        private StripeGatewayInterface $stripe,
        private UserRepInterface $userRep,
    )
    {
    }

    public function execute(User $user): SetupCardResponse
    {
        $customerId= $user->stripe_customer_id;
        if(!$customerId)
        {
            $createdCustomerId=$this->stripe->createStripeUser($user);
            $this->userRep->update($user->id, ['stripe_customer_id' => $createdCustomerId]);
            $customerId=$createdCustomerId;
        }
        $session = $this->stripe->createSetupSession($customerId);
        return PaymentMethodMapper::toSetupCardResponse($session);
    }
}
