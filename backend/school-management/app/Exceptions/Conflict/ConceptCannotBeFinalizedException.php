<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ConceptCannotBeFinalizedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(409, 'No se puede finalizar un concepto eliminado.', ErrorCode::CONCEPT_CANNOT_BE_FINALIZED);
    }
}
