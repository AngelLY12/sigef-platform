<?php

namespace App\Exceptions\Unauthorized;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class RefreshTokenExpiredException extends DomainException
{
    public function __construct(string $message = 'Refresh token expirado')
    {
        parent::__construct(401, $message, ErrorCode::REFRESH_TOKEN_EXPIRED);
    }

}
