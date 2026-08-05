<?php

namespace App\Core\Infraestructure\Repositories\Query\User;

use App\Core\Application\DTO\Response\Parents\ParentChildrenResponse;
use App\Core\Application\DTO\Response\Parents\StudentParentsResponse;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Repositories\Query\User\ParentStudentQueryRepInterface;
use App\Models\ParentStudent as EloquentParentStudent;


class EloquentParentStudentQueryRepository implements ParentStudentQueryRepInterface
{
    public function getStudentsOfParent(User $parent): ParentChildrenResponse
    {
        $relations = EloquentParentStudent::with(
            'student:id,name,last_name'
        )
            ->where('parent_id', $parent->id)
            ->get();

        $childrenData = $relations->map(fn ($relation) => [
            'id' => $relation->student->id,
            'fullName' => "{$relation->student->name} {$relation->student->last_name}",
            'relationship' => $relation->relationship,
        ])->toArray();

        return new ParentChildrenResponse(
            parentId: $parent->id,
            parentName: "{$parent->name} {$parent->last_name}",
            childrenData: $childrenData,
        );
    }
    public function getParentsOfStudent(User $student): StudentParentsResponse
    {
        $relations = EloquentParentStudent::with(
            'parent:id,name,last_name'
        )
            ->where('student_id', $student->id)
            ->get();

        $parentsData = $relations->map(fn ($relation) => [
            'id' => $relation->parent->id,
            'fullName' => "{$relation->parent->name} {$relation->parent->last_name}",
            'relationship' => $relation->relationship,
        ])->toArray();

        return new StudentParentsResponse(
            studentId: $student->id,
            studentName: "{$student->name} {$student->last_name}",
            parentsData: $parentsData,
        );
    }
    public function exists(int $parentId, int $studentId): bool
    {
        return EloquentParentStudent::where('parent_id', $parentId)
            ->where('student_id', $studentId)
            ->exists();
    }
}
