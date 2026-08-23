<?php

namespace App\Core\Application\UseCases\Admin\Events\PaymentEvents;

use App\Core\Domain\Repositories\Query\Events\PaymentEventQueryRepInterface;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Exceptions\NotFound\PaymentNotFountException;
use Illuminate\Support\Collection;

class GetPaymentTimelineUseCase
{
    public function __construct(
        private PaymentEventQueryRepInterface $paymentEventQueryRep,
        private PaymentQueryRepInterface $paymentQueryRep
    ){}

    public function execute(int $paymentId): Collection
    {
        if(!$this->paymentQueryRep->existsById($paymentId)){
            throw new PaymentNotFountException();
        }
        return $this->paymentEventQueryRep->getPaymentTimeline($paymentId);
    }

}
