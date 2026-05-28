<?php

namespace App\Core\Infraestructure\Repositories\Query\Misc;

use App\Core\Domain\Entities\ParentInvite;
use App\Core\Domain\Repositories\Query\Misc\ParentInviteQueryRepInterface;
use App\Core\Infraestructure\Mappers\ParentInviteMapper;
use App\Models\ParentInvite as EloquentParentInvite;

class EloquentParentInviteQueryRepository implements ParentInviteQueryRepInterface
{
    public function findByToken(string $token): ?ParentInvite
    {
        return optional(EloquentParentInvite::where('token',$token)->first(), fn($eloquent)=>ParentInviteMapper::toDomain($eloquent));
    }

}
