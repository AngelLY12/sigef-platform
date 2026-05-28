<?php

namespace App\Core\Application\UseCases\Jobs;

use App\Core\Domain\Repositories\Command\Auth\RefreshTokenRepInterface;

class CleanExpiredRefreshTokenUseCase
{
   public function __construct(private RefreshTokenRepInterface $rtRepo)
        {
        }
        public function execute():int
        {
            $refresh=$this->rtRepo->deletionInvalidTokens();
            return $refresh;
        }
}
