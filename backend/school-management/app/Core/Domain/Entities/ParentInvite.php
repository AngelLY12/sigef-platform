<?php

namespace App\Core\Domain\Entities;

use Carbon\Carbon;

/**
 * @OA\Schema(
 *     schema="ParentInvite",
 *     type="object",
 *     required={"studentId","email","token","expiresAt","createdBy"},
 *     @OA\Property(property="studentId", type="integer"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="token", type="string"),
 *     @OA\Property(property="expiresAt", type="string", format="date-time"),
 *     @OA\Property(property="createdBy", type="integer"),
 *     @OA\Property(property="id", type="integer", nullable=true),
 *     @OA\Property(property="usedAt", type="string", format="date-time", nullable=true),
 * )
 */
class ParentInvite
{
    public function __construct(
        /** @var User */
        public int $studentId,
        public string $email,
        public string $token,
        public Carbon $expiresAt,
        public int $createdBy,
        public ?int $id=null,
        public ?Carbon $usedAt=null,

    ) {}

    public function isExpired(): bool
    {
        return $this->expiresAt->isPast();
    }

    public function isUsed(): bool
    {
        return !is_null($this->usedAt);
    }
}
