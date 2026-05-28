<?php
namespace App\Core\Domain\Repositories\Command\Payments;


use App\Core\Domain\Entities\Payment;


interface PaymentRepInterface {
    public function create(Payment $payment): Payment;
    public function update(int $paymentId, array $fields): Payment;
    public function delete(int $paymentId):void;
}
