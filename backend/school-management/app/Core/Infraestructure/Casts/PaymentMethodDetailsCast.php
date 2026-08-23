<?php

namespace App\Core\Infraestructure\Casts;

use App\Core\Application\Factories\Payments\Stripe\StripePaymentMethodDetailsFactory;
use App\Core\Domain\ValueObjects\Payment\PaymentMethodDetails\PaymentMethodDetails;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class PaymentMethodDetailsCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?PaymentMethodDetails
    {
        if ($value === null) {
            return null;
        }

        $data = json_decode($value, true);

        return StripePaymentMethodDetailsFactory::fromArray($data);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof PaymentMethodDetails) {
            throw new \InvalidArgumentException(
                'payment_method_details debe ser un PaymentMethodDetails'
            );
        }

        return json_encode($value->toArray());
    }


}
