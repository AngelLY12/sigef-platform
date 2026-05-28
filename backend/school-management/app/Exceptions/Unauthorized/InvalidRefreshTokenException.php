<?php

namespace App\Exceptions\Unauthorized;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class InvalidRefreshTokenException extends DomainException
{
    public function __construct(string $message = 'Refresh token inválido')
    {
        parent::__construct(401, $message, ErrorCode::INVALID_REFRESH_TOKEN);
    }

}
