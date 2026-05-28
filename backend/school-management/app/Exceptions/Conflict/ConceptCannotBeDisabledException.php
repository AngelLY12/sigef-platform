<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ConceptCannotBeDisabledException extends DomainException
{
    public function __construct()
    {
        parent::__construct(409, 'No se puede desactivar un concepto finalizado.', ErrorCode::CONCEPT_CANNOT_BE_DISABLED);
    }
}
