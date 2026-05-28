<?php

namespace App\Core\Application\UseCases\Payments\Staff\Dashboard;

use App\Core\Application\DTO\Response\General\StripePayoutResponse;
use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Domain\Repositories\Stripe\StripeGatewayInterface;

class CreatePayoutUseCase
{
    public function __construct(
        private StripeGatewayInterface $gateway,
    ){}

    public function execute(): StripePayoutResponse
    {
        return GeneralMapper::toStripePayoutResponse( $this->gateway->createPayout());
    }

}
