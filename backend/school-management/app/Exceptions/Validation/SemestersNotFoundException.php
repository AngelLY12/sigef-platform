<?php

namespace App\Exceptions\Validation;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class SemestersNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct(422, 'No se especificaron semestres.', ErrorCode::SEMESTERS_NOT_FOUND);
    }
}
