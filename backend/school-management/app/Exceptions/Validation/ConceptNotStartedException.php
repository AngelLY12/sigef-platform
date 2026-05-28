<?php

namespace App\Exceptions\Validation;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ConceptNotStartedException extends DomainException
{
    public function __construct($message)
    {
        parent::__construct(422, $message, ErrorCode::CONCEPT_NOT_STARTED);
    }
}
