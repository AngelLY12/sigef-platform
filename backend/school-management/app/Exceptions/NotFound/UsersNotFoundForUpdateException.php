<?php

namespace App\Exceptions\NotFound;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class UsersNotFoundForUpdateException extends DomainException
{
    public function __construct()
    {
        parent::__construct(404, "No se encontraron usuarios que coincidan con los criterios proporcionados.", ErrorCode::USERS_NOT_FOUND_FOR_UPDATE);
    }
}
