<?php

namespace App\Exceptions\Validation;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ConceptEndDateTooFarException extends DomainException
{
    public function __construct()
    {
        parent::__construct(422, 'La fecha de fin no puede exceder 5 años desde la fecha de inicio.', ErrorCode::CONCEPT_END_DATE_TOO_FAR);
    }
}
