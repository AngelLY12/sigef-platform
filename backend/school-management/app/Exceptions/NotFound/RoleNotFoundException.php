<?php

namespace App\Exceptions\NotFound;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class RoleNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct(404, 'No se encontro el rol solicitado', ErrorCode::ROLE_NOT_FOUND);
    }
}
