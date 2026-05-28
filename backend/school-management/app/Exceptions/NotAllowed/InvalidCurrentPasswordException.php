<?php

namespace App\Exceptions\NotAllowed;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class InvalidCurrentPasswordException extends DomainException
{
    public function __construct()
    {
        parent::__construct(403, "La contraseña que proporcionaste es incorrecta.", ErrorCode::INVALID_CURRENT_PASSWORD);
    }
}
