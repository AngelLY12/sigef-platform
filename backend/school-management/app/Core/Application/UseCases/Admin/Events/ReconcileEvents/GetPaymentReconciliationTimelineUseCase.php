<?php

namespace App\Core\Application\UseCases\Admin\Events\ReconcileEvents;

use App\Core\Domain\Repositories\Query\Events\ReconciliationEventQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use Illuminate\Support\Collection;

class GetPaymentReconciliationTimelineUseCase
{
    public function __construct(
        private ReconciliationEventQueryRepInterface $reconciliationEventQueryRep,
        private PaymentQueryRepInterface $paymentQueryRep

    ){}

    public function execute(int $paymentId): Collection
    {
        return $this->reconciliationEventQueryRep->getPaymentReconciliationTimeline($paymentId);

    }


}
