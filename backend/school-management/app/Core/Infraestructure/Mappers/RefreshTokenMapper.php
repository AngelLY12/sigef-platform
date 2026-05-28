<?php

namespace App\Core\Infraestructure\Mappers;

use App\Core\Domain\Entities\RefreshToken as DomainRefreshToken;
use App\Models\RefreshToken;
use Carbon\CarbonImmutable;

class RefreshTokenMapper
{
    public static function toDomain(RefreshToken $token): DomainRefreshToken
    {
        return new DomainRefreshToken(
            id:$token->id,
            user_id: $token->user_id,
            token:$token->token,
            expiresAt: $token->expires_at ? new CarbonImmutable($token->expires_at):null,
            revoked: $token->revoked
        );
    }

    public static function toPersistence(int $userId, string $token, int $days): array
    {
        return [
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => now()->addDays($days),
            'revoked' => false,
        ];
    }
}
