<?php

namespace App\Core\Application\UseCases\Jobs;

use App\Core\Domain\Repositories\Command\Misc\ParentInviteRepInterface;

class CleanExpiredInvitesUseCase
{
    public function __construct(
        private ParentInviteRepInterface $inviteRepo
    )
    {
    }

    public function execute():int
    {
        return $this->inviteRepo->deleteExpired();
    }
}
