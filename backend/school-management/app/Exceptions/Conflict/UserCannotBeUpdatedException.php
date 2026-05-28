<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class UserCannotBeUpdatedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(409, 'No se puede actualizar un usuario que no esté activo.', ErrorCode::USER_CANNOT_BE_UPDATED);
    }
}
