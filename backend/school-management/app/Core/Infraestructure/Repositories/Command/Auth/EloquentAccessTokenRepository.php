<?php

namespace App\Core\Infraestructure\Repositories\Command\Auth;

use App\Core\Domain\Repositories\Command\Auth\AccessTokenRepInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class EloquentAccessTokenRepository implements AccessTokenRepInterface
{
    public function revokeToken(int $tokenId): bool
    {
        return PersonalAccessToken::whereKey($tokenId)->delete() === 1;

    }

    public function deletionInvalidTokens(): int
    {
        $now = Carbon::now();
        $deleted = DB::table('personal_access_tokens')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now)
            ->delete();
        return $deleted;
    }

}
