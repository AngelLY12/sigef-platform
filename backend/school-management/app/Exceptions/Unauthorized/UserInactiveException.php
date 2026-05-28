<?php

namespace App\Exceptions\Unauthorized;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class UserInactiveException extends DomainException
{
    public function __construct()
    {
        parent::__construct(401, "Tu sesión ya no es válida. El usuario fue desactivado.", ErrorCode::USER_INACTIVE);
    }
}
