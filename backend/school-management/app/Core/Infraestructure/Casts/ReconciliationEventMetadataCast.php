<?php

namespace App\Core\Infraestructure\Casts;

use App\Core\Application\Factories\Payments\ReconciliationEventMetadataFactory;
use App\Core\Domain\ValueObjects\Payment\PaymentEvents\Metadata\PaymentEventMetadata;
use App\Core\Domain\ValueObjects\Payment\ReconciliationEvents\ReconciliationEventMetadata;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class ReconciliationEventMetadataCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }

        $data = json_decode($value, true);
        $outcome = $model->outcome;
        ReconciliationEventMetadataFactory::fromArray(outcome: $outcome, data: $data);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        if ($value === null) {
            return null;
        }
        if(!$value instanceof ReconciliationEventMetadata)
        {
            throw new \InvalidArgumentException(
                'metadata debe ser un ReconciliationEventMetadata'
            );
        }

        return json_encode($value->toArray());
    }

}
