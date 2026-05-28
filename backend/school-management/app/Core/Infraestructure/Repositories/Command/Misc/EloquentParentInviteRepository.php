<?php

namespace App\Core\Infraestructure\Repositories\Command\Misc;

use App\Core\Domain\Entities\ParentInvite;
use App\Core\Domain\Repositories\Command\Misc\ParentInviteRepInterface;
use App\Core\Infraestructure\Mappers\ParentInviteMapper;
use App\Models\ParentInvite as EloquentParentInvite;

class EloquentParentInviteRepository implements ParentInviteRepInterface
{
    public function create(ParentInvite $invite): ParentInvite
    {
        $eloquent= EloquentParentInvite::create(ParentInviteMapper::toPersistence($invite));
        $eloquent->refresh();
        return ParentInviteMapper::toDomain($eloquent);
    }
    public function markAsUsed(int $id): bool
    {
        return EloquentParentInvite::where('id', $id)
            ->update(['used_at' => now()]);
    }
    public function deleteExpired(): int
    {
        return EloquentParentInvite::where('expires_at', '<', now())->delete();

    }
}
