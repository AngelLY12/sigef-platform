<?php

namespace App\Exceptions\Validation;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ConceptInvalidAmountException extends DomainException
{
    public function __construct()
    {
        parent::__construct(422, 'El monto del concepto debe ser mayor a $10.00 y menor a $25000.00.', ErrorCode::CONCEPT_INVALID_AMOUNT);
    }
}
