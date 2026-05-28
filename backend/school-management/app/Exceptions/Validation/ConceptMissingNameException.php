<?php

namespace App\Exceptions\Validation;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ConceptMissingNameException extends DomainException
{
    public function __construct()
    {
        parent::__construct(422, 'El concepto debe tener un nombre válido.', ErrorCode::CONCEPT_MISSING_NAME);
    }
}
