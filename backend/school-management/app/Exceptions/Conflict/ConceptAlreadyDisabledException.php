<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ConceptAlreadyDisabledException extends DomainException
{
    public function __construct()
    {
        parent::__construct(409, 'El concepto ya está desactivado.', ErrorCode::CONCEPT_ALREADY_DISABLED);
    }
}
