<?php

namespace App\Core\Application\UseCases\Payments\Student\PendingPayment;

use App\Core\Domain\Entities\User;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Exceptions\Unauthorized\UserInactiveException;

class ShowPendingPaymentsUseCase
{

    public function __construct(
        private PaymentConceptQueryRepInterface $pcqRepo,
    ) {}

    public function execute(User $user): array {
        if (!$user->isActive()) {
            throw new UserInactiveException();
        }
        $pendingArray=$this->pcqRepo->getPendingPaymentConceptsWithDetails($user);
        return $pendingArray;
    }
}
