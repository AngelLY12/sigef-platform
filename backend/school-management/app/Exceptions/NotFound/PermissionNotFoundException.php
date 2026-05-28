<?php

namespace App\Exceptions\NotFound;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class PermissionNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct(404, 'No se encontro el permiso seleccionado', ErrorCode::PERMISSION_NOT_FOUND);
    }
}
