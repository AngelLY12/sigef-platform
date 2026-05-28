<?php

namespace App\Core\Infraestructure\Repositories\Query\Misc;

use App\Core\Domain\Entities\Career;
use App\Core\Domain\Repositories\Query\Misc\CareerQueryRepInterface;
use App\Core\Infraestructure\Mappers\CareerMapper;
use App\Models\Career as EloquentCareer;

class EloquentCareerQueryRepository implements CareerQueryRepInterface
{
    public function findByName(string $careerName): ?Career
    {
        $career = EloquentCareer::where('career_name', $careerName)->first();
        return $career ? CareerMapper::toDomain($career) : null;
    }

    public function findAll():array
    {
        return EloquentCareer::all()
        ->map(fn($career) => CareerMapper::toDomain($career))
        ->toArray();
    }

    public function findAllIds(): array
    {
        return EloquentCareer::query()->pluck('id')->toArray();

    }

    public function findById(int $id): ?Career
    {
        return optional(EloquentCareer::find($id), fn($career) => CareerMapper::toDomain($career));

    }

    public function exists(int $id): bool
    {
        return EloquentCareer::where('id',$id)->exists();
    }

}
