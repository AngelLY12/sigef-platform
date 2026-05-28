<?php

namespace App\Core\Application\Mappers;

use App\Core\Application\DTO\Response\Parents\ParentChildrenResponse;
use App\Core\Application\DTO\Response\Parents\StudentParentsResponse;
use App\Core\Domain\Entities\ParentStudent;
use App\Core\Domain\Enum\User\RelationshipType;

class ParentStudentMapper
{
    public static function toDomain(array $data): ParentStudent
    {
        return new ParentStudent(
            parentId:$data['parentId'],
            studentId:$data['studentId'],
            parentRoleId:$data['parentRoleId'],
            studentRoleId:$data['studentRoleId'],
            relationship: isset($data['relationship'])
            ? RelationshipType::from(strtolower($data['relationship']))
            : null
        );
    }

    public static function toParentChildrenResponse(array $data): ParentChildrenResponse
    {
        return new ParentChildrenResponse(
            parentId:$data['parentId'],
            parentName:$data['parentName'],
            childrenData:$data['childrenData'] ?? []
        );
    }

    public static function toStudentParentsResponse(array $data): StudentParentsResponse
    {
        return new StudentParentsResponse(
            studentId:$data['studentId'],
            studentName:$data['studentName'],
            parentsData: $data['parentsData'] ?? []
        );
    }
}
