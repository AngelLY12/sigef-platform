<?php

namespace App\Exceptions\NotFound;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class PermissionsByUserNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct(404, 'No se encontraron permisos aplicables para los usuarios seleccionados', ErrorCode::PERMISSIONS_BY_USER_NOT_FOUND);
    }
}
