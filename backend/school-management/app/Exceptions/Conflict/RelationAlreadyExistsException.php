<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class RelationAlreadyExistsException extends DomainException
{
    public function __construct()
    {
        parent::__construct(409, "Un pariente con este email ya fue enlazado al estudiante.", ErrorCode::RELATION_ALREADY_EXISTS);
    }
}
