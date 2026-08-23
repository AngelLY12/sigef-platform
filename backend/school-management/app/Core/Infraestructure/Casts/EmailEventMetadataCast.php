<?php

namespace App\Core\Infraestructure\Casts;

use App\Core\Application\Factories\Emails\Events\EmailEventMetadataFactory;
use App\Core\Domain\ValueObjects\EmailEvent\EmailEventMetadata;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class EmailEventMetadataCast implements CastsAttributes
{

    public function get(Model $model, string $key, mixed $value, array $attributes) : ?EmailEventMetadata
    {
        if ($value === null) {
            return null;
        }

        $data = json_decode($value, true);

        return EmailEventMetadataFactory::fromArray($data);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof EmailEventMetadata) {
            throw new \InvalidArgumentException(
                'metadata debe ser un EmailEventMetadata'
            );
        }

        return json_encode($value->toArray());
    }

}
