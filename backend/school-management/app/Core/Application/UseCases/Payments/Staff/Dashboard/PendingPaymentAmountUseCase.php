<?php

namespace App\Core\Application\UseCases\Payments\Staff\Dashboard;

use App\Core\Application\DTO\Response\PaymentConcept\PendingSummaryResponse;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;

class PendingPaymentAmountUseCase{
    public function __construct(
        private PaymentConceptQueryRepInterface $pcqRepo
    )
    {
    }
    public function execute(bool $onlyThisYear): PendingSummaryResponse
    {
        return $this->pcqRepo->getAllPendingPaymentAmount($onlyThisYear);
    }
}
