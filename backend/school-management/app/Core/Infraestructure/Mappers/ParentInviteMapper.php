<?php

namespace App\Core\Infraestructure\Mappers;

use App\Core\Domain\Entities\ParentInvite as EntitiesParentInvite;
use App\Models\ParentInvite;

class ParentInviteMapper
{
    public static function toDomain(ParentInvite $invite): EntitiesParentInvite
    {
        return new EntitiesParentInvite(
            studentId: $invite->student_id,
            email: $invite->email,
            token: $invite->token,
            expiresAt: $invite->expires_at,
            createdBy: $invite->created_by,
            id: $invite->id,
            usedAt: $invite->used_at
        );
    }

    public static function toPersistence(EntitiesParentInvite $invite): array
    {
        return [
            'student_id' => $invite->studentId,
            'email' => $invite->email,
            'token' => $invite->token,
            'expires_at' => $invite->expiresAt,
            'created_by' => $invite->createdBy,
        ];
    }
}
