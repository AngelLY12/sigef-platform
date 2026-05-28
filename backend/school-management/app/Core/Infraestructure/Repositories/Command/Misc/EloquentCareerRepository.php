<?php

namespace App\Core\Infraestructure\Repositories\Command\Misc;

use App\Core\Domain\Entities\Career;
use App\Core\Domain\Repositories\Command\Misc\CareerRepInterface;
use App\Models\Career as EloquentCareer;
use App\Core\Infraestructure\Mappers\CareerMapper;

class EloquentCareerRepository implements CareerRepInterface
{

    public function create(Career $career): Career
    {
        $career=EloquentCareer::create(CareerMapper::toPersistence($career));
        $career->refresh();
        return CareerMapper::toDomain($career);
    }

    public function delete(int $careerId): void
    {
        $eloquent=$this->findOrFail($careerId);
        $eloquent->delete();
    }

    public function update(int $careerId, array $fields): Career
    {
        $eloquent=$this->findOrFail($careerId);
        $eloquent->update($fields);
        return CareerMapper::toDomain($eloquent);
    }

     private function findOrFail(int $id): EloquentCareer
    {
        return EloquentCareer::findOrFail($id);
    }
}
