<?php

namespace App\Core\Application\UseCases\Payments\Student\PendingPayment;

use App\Core\Domain\Entities\User;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;

class ShowOverduePaymentsUseCase
{
    public function __construct(
        private PaymentConceptQueryRepInterface $pcqRepo,
    ) {}
    public function execute(User $user): array {
        return $this->pcqRepo->getOverduePayments($user);
    }

}
