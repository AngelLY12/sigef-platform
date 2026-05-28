<?php

namespace App\Core\Application\UseCases\Payments\Student\PaymentHistory;

use App\Core\Application\DTO\Response\Payment\PaymentToDisplay;
use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Repositories\Query\Payments\PaymentQueryRepInterface;
use App\Exceptions\NotFound\PaymentNotFountException;

class FindPaymentByIdUseCase
{
    public function __construct(
        private PaymentQueryRepInterface $paymentRepo
    )
    {
    }

    public function execute(int $id): PaymentToDisplay
    {
        $payment = $this->paymentRepo->findByIdToDisplay($id);
        if(!$payment)
        {
            throw new PaymentNotFountException();
        }
        return $payment;
    }
}
