<?php

namespace App\Exceptions\NotAllowed;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class UserNotAllowedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(403, "El usuario no tiene permitido pagar este concepto", ErrorCode::USER_NOT_ALLOWED);
    }
}
