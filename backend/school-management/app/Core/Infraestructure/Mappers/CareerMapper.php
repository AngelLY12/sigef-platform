<?php

namespace App\Core\Infraestructure\Mappers;

use App\Models\Career;
use App\Core\Domain\Entities\Career as DomainCareer;

class CareerMapper{
    public static function toDomain(Career $career): DomainCareer{
        return new DomainCareer(
            career_name: $career->career_name,
            id: $career->id
        );
    }

    public static function toPersistence(DomainCareer $career): array
    {
        return ['career_name'=>$career->career_name];
    }

}

