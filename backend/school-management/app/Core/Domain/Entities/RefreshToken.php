<?php

namespace App\Core\Domain\Entities;
use Carbon\CarbonImmutable;

/**
 * @OA\Schema(
 *     schema="DomainRefreshToken",
 *     type="object",
 *     description="Representa un token de actualización para un usuario",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=123),
 *     @OA\Property(property="token", type="string", example="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."),
 *     @OA\Property(property="expiresAt", type="string", format="date-time", example="2025-11-04T23:59:59Z"),
 *     @OA\Property(property="revoked", type="boolean", example=false)
 * )
 */
class RefreshToken
{
    public function __construct(
        public readonly int $id,
        /** @var User */
        public readonly int $user_id,
        public readonly string $token,
        public readonly CarbonImmutable $expiresAt,
        public bool $revoked = false
    ) {}

    public function isExpired(): bool
    {
        return $this->expiresAt->isPast();
    }

    public function isValid(): bool
    {
        return !$this->revoked && !$this->isExpired();
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

}
