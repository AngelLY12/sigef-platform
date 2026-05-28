<?php

namespace App\Core\Infraestructure\Repositories\Command\Auth;

use App\Core\Domain\Entities\RefreshToken as EntitiesRefreshToken;
use App\Core\Domain\Repositories\Command\Auth\RefreshTokenRepInterface;
use App\Core\Infraestructure\Mappers\RefreshTokenMapper;
use App\Models\RefreshToken as ModelsRefreshToken;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class EloquentRefreshTokenRepository implements RefreshTokenRepInterface
{
    public function findByToken(string $token): ?EntitiesRefreshToken
    {
       $hashedToken = hash('sha256', $token);

        $eloquent = ModelsRefreshToken::where('token', $hashedToken)->first();

        if (!$eloquent) {
            throw new ModelNotFoundException('El token no fue encontrado');
        }

        return RefreshTokenMapper::toDomain($eloquent);
    }

    public function revokeRefreshToken(string $refreshTokenValue): bool
    {
        $affected = DB::table('refresh_tokens')
            ->where('token', hash('sha256', $refreshTokenValue))
            ->where('revoked', false)
            ->update(['revoked' => true]);

        return $affected === 1;

    }
    public function update(int $tokenId, array $fields): EntitiesRefreshToken
    {
        $eloquentToken =  $this->findOrFail($tokenId);
        $eloquentToken->update($fields);
        $eloquentToken->refresh();
        return RefreshTokenMapper::toDomain($eloquentToken);

    }

    public function delete(int $tokenId):void
    {
        $eloquent=$this->findOrFail($tokenId);
        $eloquent->delete();
    }

    public function deletionInvalidTokens(): int
    {
        $now = now();

        DB::table('refresh_tokens')
            ->where('expires_at', '<', $now)
            ->where('revoked', false)
            ->update(['revoked' => true]);

        $deleted = DB::table('refresh_tokens')
            ->where(function ($query) use ($now) {
                $query->where('revoked', true)
                    ->orWhere('expires_at', '<', $now);
            })
            ->delete();

        return $deleted;
    }

    private function findOrFail(int $tokenId):ModelsRefreshToken
    {
        return ModelsRefreshToken::findOrFail($tokenId);
    }


}
