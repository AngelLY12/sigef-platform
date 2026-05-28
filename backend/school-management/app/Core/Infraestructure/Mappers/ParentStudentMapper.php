<?php

namespace App\Core\Infraestructure\Mappers;

use App\Core\Domain\Entities\ParentStudent as EntitiesParentStudent;
use App\Models\ParentStudent;

class ParentStudentMapper
{
    public static function toDomain(ParentStudent $relation): EntitiesParentStudent
    {
        return new EntitiesParentStudent(
            parentId:$relation->parent_id,
            studentId:$relation->student_id,
            parentRoleId:$relation->parent_role_id,
            studentRoleId: $relation->student_role_id,
            relationship:$relation->relationship
        );
    }
    public static function toPersistence(EntitiesParentStudent $data): array
    {
        return [
            'parent_id' => $data->parentId,
            'student_id' => $data->studentId,
            'parent_role_id' => $data->parentRoleId,
            'student_role_id' => $data->studentRoleId,
            'relationship' => $data->relationship,
        ];

    }
}
