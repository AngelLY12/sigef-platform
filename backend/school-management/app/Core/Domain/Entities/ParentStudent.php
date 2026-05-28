<?php

namespace App\Core\Domain\Entities;

use App\Core\Domain\Enum\User\RelationshipType;

/**
 * @OA\Schema(
 *     schema="ParentStudent",
 *     type="object",
 *     required={"parentId","studentId","parentRoleId","studentRoleId"},
 *     @OA\Property(property="parentId", type="integer"),
 *     @OA\Property(property="studentId", type="integer"),
 *     @OA\Property(property="parentRoleId", type="integer"),
 *     @OA\Property(property="studentRoleId", type="integer"),
 *     @OA\Property(property="relationship", ref="#/components/schemas/RelationshipType", nullable=true)
 * )
 */
class ParentStudent
{
    public function __construct(
        /** @var User */
        public readonly int $parentId,
        /** @var User */
        public readonly int $studentId,
        /** @var Role */
        public readonly int $parentRoleId,
        /** @var Role */
        public readonly int $studentRoleId,
        /** @var RelationshipType */
        public readonly ?RelationshipType $relationship = null,
    ) {}
}
