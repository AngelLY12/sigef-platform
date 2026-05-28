<?php

namespace App\Exceptions\NotFound;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class UserNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct(404, "Usuario no encontrado", ErrorCode::USER_NOT_FOUND);
    }
}
