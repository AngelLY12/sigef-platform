<?php

namespace App\Core\Application\UseCases\Payments\Student\Dashboard;

use App\Core\Application\DTO\Response\PaymentConcept\PendingSummaryResponse;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;

class OverduePaymentsUseCase
{
    public function __construct(
        private PaymentConceptQueryRepInterface $pcqRepo,
    ) {}
    public function execute(User $user, bool $onlyThisYear): PendingSummaryResponse {
        return $this->pcqRepo->getOverduePaymentsSummary($user, $onlyThisYear);
    }
}
