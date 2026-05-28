<?php

namespace App\Exceptions\NotAllowed;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class UserInvalidRoleException extends DomainException
{
    public function __construct()
    {
        parent::__construct(403, "El usuario no tiene el rol necesario.", ErrorCode::USER_INVALID_ROLE);
    }
}
