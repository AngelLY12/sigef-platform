<?php
namespace App\Core\Infraestructure\Repositories\Command\Payments;


use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Repositories\Command\Payments\PaymentRepInterface;
use App\Core\Infraestructure\Mappers\PaymentMapper;
use App\Models\Payment as EloquentPayment;

class EloquentPaymentRepository implements PaymentRepInterface {


    public function create(Payment $payment): Payment
    {
        $pm = EloquentPayment::create(PaymentMapper::toPersistence($payment));
        $pm->refresh();
        return PaymentMapper::toDomain($pm);

    }

    public function update(int $paymentId, array $fields): Payment
    {
        $eloquentPayment = EloquentPayment::findOrFail($paymentId);
        $eloquentPayment->update($fields);
        return PaymentMapper::toDomain($eloquentPayment);

    }

    public function delete(int $paymentId): void
    {
        EloquentPayment::findOrFail($paymentId)->delete();
    }

}

