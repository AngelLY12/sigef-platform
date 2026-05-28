<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ConceptAppliesToConflictException extends DomainException
{
    public function __construct()
    {
        parent::__construct(409, 'Si el concepto aplica a todos, no se deben enviar carreras, semestres o estudiantes específicos.', ErrorCode::CONCEPT_APPLIES_TO_CONFLICT);
    }
}
