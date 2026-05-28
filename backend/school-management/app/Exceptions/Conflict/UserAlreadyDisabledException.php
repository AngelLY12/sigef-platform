<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class UserAlreadyDisabledException extends DomainException
{
    public function __construct()
    {
        parent::__construct(409, 'El usuario ya fue dado de baja', ErrorCode::USER_ALREADY_DISABLED);
    }
}
