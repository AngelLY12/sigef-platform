<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ConceptCannotBeUpdatedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(409, 'No se puede actualizar un concepto que no esté activo o desactivado.', ErrorCode::CONCEPT_CANNOT_BE_UPDATED);
    }
}
