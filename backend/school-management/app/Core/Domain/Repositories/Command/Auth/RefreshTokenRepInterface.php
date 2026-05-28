<?php

namespace App\Core\Domain\Repositories\Command\Auth;

use App\Core\Domain\Entities\RefreshToken;

interface RefreshTokenRepInterface
{
    public function findByToken(string $token): ?RefreshToken;
    public function revokeRefreshToken(string $refreshTokenValue): bool;
    public function update(int $tokenId,  array $fields): RefreshToken;
    public function delete(int $tokenId): void;
    public function deletionInvalidTokens(): int;
}
