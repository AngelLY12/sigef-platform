<?php

namespace App\Core\Infraestructure\Mappers;

use App\Models\PaymentMethod;
use App\Core\Domain\Entities\PaymentMethod as DomainPaymentMethod;

class PaymentMethodMapper{

    public static function toDomain(PaymentMethod $paymentMethod){
        return new DomainPaymentMethod(
            user_id: $paymentMethod->user_id,
            stripe_payment_method_id: $paymentMethod->stripe_payment_method_id,
            brand: $paymentMethod->brand,
            last4: $paymentMethod->last4,
            exp_month: $paymentMethod->exp_month,
            exp_year: $paymentMethod->exp_year,
            id: $paymentMethod->id
        );
    }
    public static function toPersistence(DomainPaymentMethod $paymentMethod): array
    {
        return [
            'user_id' => $paymentMethod->user_id,
            'stripe_payment_method_id' => $paymentMethod->stripe_payment_method_id,
            'brand' => $paymentMethod->brand,
            'last4' => $paymentMethod->last4,
            'exp_month' => $paymentMethod->exp_month,
            'exp_year' => $paymentMethod->exp_year,
        ];
    }
}
