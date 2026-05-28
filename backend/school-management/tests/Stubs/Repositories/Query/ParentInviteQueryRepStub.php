<?php

namespace Tests\Stubs\Repositories\Query;
use App\Core\Domain\Repositories\Query\Misc\ParentInviteQueryRepInterface;
use App\Core\Domain\Entities\ParentInvite;

class ParentInviteQueryRepStub implements ParentInviteQueryRepInterface
{
    private ?ParentInvite $nextFindByTokenResult = null;

    public function findByToken(string $token): ?ParentInvite
    {
        return $this->nextFindByTokenResult;
    }

    public function setNextFindByTokenResult(?ParentInvite $invite): self
    {
        $this->nextFindByTokenResult = $invite;
        return $this;
    }
}
