<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class UserAlreadyActiveException extends DomainException
{
    public function __construct()
    {
        parent::__construct(409, 'El usuario ya esta activo', ErrorCode::USER_ALREADY_ACTIVE);
    }
}
