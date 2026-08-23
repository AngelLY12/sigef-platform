<?php

namespace App\Core\Application\Services\Reconciliation;

use App\Core\Application\DTO\Response\Payment\PaymentReconciliationResponse;
use App\Core\Domain\Entities\Payment;
use Illuminate\Support\Collection;

interface PaymentReconciliationServiceInterface
{
    public function reconcile(
        Payment $payment,
        Collection $events,
        string $operationId
    ): PaymentReconciliationResponse;
}
