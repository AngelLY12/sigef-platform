<?php

namespace App\Core\Application\UseCases\Jobs;

use App\Core\Domain\Repositories\Command\Payments\PaymentEventRepInterface;

class CleanOlderPaymentEventsUseCase
{
    public function __construct(
        private PaymentEventRepInterface $paymentEventRep,
    ){}

    public function execute(): int
    {
        return $this->paymentEventRep->deleteOlderEvents();
    }

}
