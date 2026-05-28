<?php

namespace App\Core\Domain\Repositories\Query\Misc;

use App\Core\Domain\Entities\ParentInvite;

interface ParentInviteQueryRepInterface
{
    public function findByToken(string $token): ?ParentInvite;

}
