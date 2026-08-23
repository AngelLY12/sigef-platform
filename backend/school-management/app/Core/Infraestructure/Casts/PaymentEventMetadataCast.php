<?php

namespace App\Core\Infraestructure\Casts;

use App\Core\Application\Factories\Payments\Stripe\PaymentEventMetadataFactory;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\PaymentEventMetadata;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PaymentEventMetadataCast implements CastsAttributes
{

    public function get(Model $model, string $key, mixed $value, array $attributes): ?PaymentEventMetadata
    {
        if ($value === null) {
            return null;
        }

        $data = json_decode($value, true);

        return PaymentEventMetadataFactory::fromArray($data);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof PaymentEventMetadata) {
            throw new \InvalidArgumentException(
                'metadata debe ser un PaymentEventMetadata'
            );
        }

        return json_encode($value->toArray());
    }

}
