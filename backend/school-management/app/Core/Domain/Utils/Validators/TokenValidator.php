<?php

namespace App\Core\Domain\Utils\Validators;

use App\Core\Domain\Entities\RefreshToken;
use App\Exceptions\Unauthorized\InvalidRefreshTokenException;
use App\Exceptions\Unauthorized\RefreshTokenExpiredException;
use App\Exceptions\Unauthorized\RefreshTokenRevokedException;
use Illuminate\Auth\AuthenticationException;

class TokenValidator
{
    public static function ensureIsTokenValid(RefreshToken $token)
    {

        if ($token->isExpired()) {
            throw new RefreshTokenExpiredException("Refresh token expirado");
        }
        if ($token->isRevoked()) {
            throw new RefreshTokenRevokedException("Refresh token revocado");
        }

         if (!$token->isValid()) {
            throw new InvalidRefreshTokenException("Refresh token inválido");

        }
    }
}
