<?php

namespace App\Core\Application\UseCases\Payments\Student\Cards;

use App\Core\Application\Mappers\PaymentMethodMapper;
use App\Core\Domain\Repositories\Query\Payments\PaymentMethodQueryRepInterface;

class GetUserPaymentMethodsUseCase
{
    public function __construct(
        private PaymentMethodQueryRepInterface $pmqRepo,
    )
    {
    }
    public function execute(int $userId): array
    {
         $methods = $this->pmqRepo->getByUserId($userId);

        return array_map(
            fn($method) => PaymentMethodMapper::toDisplayPaymentMethodResponse($method),
            $methods
        );
    }

}
