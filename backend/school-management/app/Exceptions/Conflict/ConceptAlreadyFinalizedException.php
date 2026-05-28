<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ConceptAlreadyFinalizedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(409, 'El concepto ya está finalizado.', ErrorCode::CONCEPT_ALREADY_FINALIZED);
    }
}
