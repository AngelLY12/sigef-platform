<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ConceptAlreadyActiveException extends DomainException
{
    public function __construct()
    {
        parent::__construct(409, 'El concepto ya está activo.', ErrorCode::CONCEPT_ALREADY_ACTIVE);
    }
}
