<?php

namespace App\Core\Infraestructure\Repositories\Command\Payments;

use App\Core\Domain\Entities\PaymentMethod;
use App\Core\Domain\Repositories\Command\Payments\PaymentMethodRepInterface;
use App\Core\Infraestructure\Mappers\PaymentMethodMapper;
use App\Models\PaymentMethod as EloquentPaymentMethod;
use Illuminate\Support\Arr;

class EloquentPaymentMethodRepository implements PaymentMethodRepInterface
{

    public function create(PaymentMethod $paymentMethod):PaymentMethod
    {
        $data = PaymentMethodMapper::toPersistence($paymentMethod);
        $pm = EloquentPaymentMethod::updateOrCreate(
            ['stripe_payment_method_id' => $data['stripe_payment_method_id']],
            $data
        );
        return PaymentMethodMapper::toDomain($pm);
    }

    public function updateByStripeId(string $stripeId, array $fields): int
    {
        return EloquentPaymentMethod::where('stripe_payment_method_id', $stripeId)
                ->update($fields);
    }

    public function delete(int $paymentMethodId):void
    {
        EloquentPaymentMethod::findOrFail($paymentMethodId)->delete();

    }

    public function deleteByStripeId(string $stripeId): bool
    {
        $affected= EloquentPaymentMethod::where('stripe_payment_method_id', $stripeId)->delete();
        return $affected > 0;
    }

}
