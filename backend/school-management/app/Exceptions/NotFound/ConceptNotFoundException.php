<?php

namespace App\Exceptions\NotFound;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ConceptNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct(404, 'El concepto solicitado no fue encontrado.', ErrorCode::CONCEPT_NOT_FOUND);
    }
}
