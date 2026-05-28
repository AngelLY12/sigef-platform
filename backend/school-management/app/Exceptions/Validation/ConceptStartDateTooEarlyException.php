<?php

namespace App\Exceptions\Validation;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ConceptStartDateTooEarlyException extends DomainException
{
    public function __construct()
    {
        parent::__construct(422, 'La fecha de inicio del concepto no puede ser más de 1 mes antes de hoy.', ErrorCode::CONCEPT_START_DATE_TOO_EARLY);
    }
}
