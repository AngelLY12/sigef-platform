<?php

namespace App\Exceptions\Unauthorized;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class InvalidCredentialsException extends DomainException
{
    public function __construct(?string $message=null)
    {
        parent::__construct(400, "Las credenciales son incorrectas. {$message}", ErrorCode::INVALID_CREDENTIALS);
    }
}
