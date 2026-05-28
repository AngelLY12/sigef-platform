<?php

namespace App\Core\Infraestructure\Repositories\Command\User;

use App\Core\Domain\Entities\ParentStudent;
use App\Core\Domain\Repositories\Command\User\ParentStudentRepInterface;
use App\Core\Infraestructure\Mappers\ParentStudentMapper;
use App\Models\ParentStudent as EloquentParentStudent;

class EloquentParentStudentRepository implements ParentStudentRepInterface
{
    public function create(ParentStudent $relation): ParentStudent
    {
        $eloquent= EloquentParentStudent::create(ParentStudentMapper::toPersistence($relation));
        $eloquent->refresh();
        return ParentStudentMapper::toDomain($eloquent);
    }
    public function update(int $parentId, int $studentId, array $fields): ParentStudent
    {
        $eloquent = EloquentParentStudent::where('parent_id', $parentId)
        ->where('student_id', $studentId)
        ->firstOrFail();
        $eloquent->update($fields);
        return ParentStudentMapper::toDomain($eloquent);
    }
    public function delete(int $parentId, int $studentId): void
    {
        $eloquent = EloquentParentStudent::where('parent_id', $parentId)
        ->where('student_id', $studentId)
        ->firstOrFail();
        $eloquent->delete();
    }

}
