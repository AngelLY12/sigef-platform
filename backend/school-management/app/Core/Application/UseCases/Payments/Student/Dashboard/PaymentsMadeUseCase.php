<?php

namespace App\Core\Application\UseCases\Payments\Student\Dashboard;

use App\Core\Application\DTO\Response\Payment\PaymentsSummaryResponse;
use App\Core\Application\Mappers\PaymentMapper;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;

class PaymentsMadeUseCase
{
      public function __construct(
        private PaymentQueryRepInterface $pqRepo,

    ) {}
    public function execute(int $userId, bool $onlyThisYear): PaymentsSummaryResponse
    {
        $payments=$this->pqRepo->sumPaymentsByUserYear($userId, $onlyThisYear);
        return PaymentMapper::toPaymentsSummaryResponse($payments);
    }
}
