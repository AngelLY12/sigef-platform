<?php

namespace App\Core\Application\Mappers;

use App\Core\Domain\Entities\Career as DomainCareer;

class CareerMapper{

    public static function toDomain(array $data): DomainCareer
    {
        return new DomainCareer(
            career_name:$data['career_name']
        );
    }

}

