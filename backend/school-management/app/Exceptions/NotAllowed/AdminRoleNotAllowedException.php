<?php

namespace App\Exceptions\NotAllowed;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class AdminRoleNotAllowedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(403, "El role de admin no esta permitido.", ErrorCode::ADMIN_ROLE_NOT_ALLOWED);
    }
}
