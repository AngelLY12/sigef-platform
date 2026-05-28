<?php

namespace App\Exceptions\Validation;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ConceptEndDateBeforeTodayException extends DomainException
{
    public function __construct()
    {
        parent::__construct(422, 'La fecha de fin no puede ser anterior a hoy.', ErrorCode::CONCEPT_END_DATE_BEFORE_TODAY);
    }
}
