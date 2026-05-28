<?php

namespace App\Exceptions\NotFound;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class StudentsNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct(404, 'Ninguno de los estudiantes existe o está dado de baja.', ErrorCode::STUDENTS_NOT_FOUND);
    }
}
