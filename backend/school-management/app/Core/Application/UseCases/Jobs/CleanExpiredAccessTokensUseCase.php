<?php

namespace App\Core\Application\UseCases\Jobs;

use App\Core\Domain\Repositories\Command\Auth\AccessTokenRepInterface;

class CleanExpiredAccessTokensUseCase
{
    public function __construct(
    private AccessTokenRepInterface $repo)
    {
    }
    public function execute():int
    {
        $sanctum=$this->repo->deletionInvalidTokens();
        return $sanctum;
    }
}
