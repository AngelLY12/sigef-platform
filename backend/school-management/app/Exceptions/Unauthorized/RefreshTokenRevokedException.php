<?php

namespace App\Exceptions\Unauthorized;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class RefreshTokenRevokedException extends DomainException
{
    public function __construct(string $message = 'Refresh token revocado')
    {
        parent::__construct(401, $message, ErrorCode::REFRESH_TOKEN_REVOKED);
    }

}
