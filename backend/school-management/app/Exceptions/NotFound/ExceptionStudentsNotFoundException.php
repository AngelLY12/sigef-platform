<?php

namespace App\Exceptions\NotFound;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ExceptionStudentsNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct(404, 'No se encontraron los estudiantes a los que se quiere hacer una excepción del concepto', ErrorCode::EXCEPTION_STUDENTS_NOT_FOUND);
    }
}
