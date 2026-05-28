<?php

namespace App\Core\Application\UseCases\Auth;

use App\Core\Domain\Repositories\Command\Auth\AccessTokenRepInterface;
use App\Core\Domain\Repositories\Command\Auth\RefreshTokenRepInterface;
use App\Exceptions\Unauthorized\InvalidRefreshTokenException;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LogoutUseCase
{
    public function __construct(
        private RefreshTokenRepInterface $refresh,
        private AccessTokenRepInterface $access,
    )
    {
    }
    public function execute(User $user, ?string $refreshTokenValue): void
    {
        DB::transaction(function () use ($user, $refreshTokenValue) {
            $accessToken = $user->currentAccessToken();
            if ($accessToken) {
                $this->access->revokeToken($accessToken->id);
            }

            if ($refreshTokenValue) {
                $revoked=$this->refresh->revokeRefreshToken($refreshTokenValue);
                if (!$revoked) {
                    throw new InvalidRefreshTokenException('Hubo un error al cerrar sesión');
                }
            }
        });
    }


}
