<?php

namespace App\Core\Domain\Repositories\Command\Auth;

interface AccessTokenRepInterface
{
    public function revokeToken(int $tokenId): bool;
    public function deletionInvalidTokens(): int;
}
