<?php

namespace App\Exceptions\Validation;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ConceptInvalidEndDateException extends DomainException
{
    public function __construct()
    {
        parent::__construct(422, 'La fecha de fin del concepto no es válida.', ErrorCode::CONCEPT_INVALID_END_DATE);
    }
}
