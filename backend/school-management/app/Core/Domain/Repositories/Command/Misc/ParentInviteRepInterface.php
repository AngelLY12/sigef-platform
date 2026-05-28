<?php

namespace App\Core\Domain\Repositories\Command\Misc;

use App\Core\Domain\Entities\ParentInvite;

interface ParentInviteRepInterface
{
    public function create(ParentInvite $invite): ParentInvite;
    public function markAsUsed(int $id): bool;
    public function deleteExpired(): int;
}
