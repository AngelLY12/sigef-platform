<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class UserAlreadyHaveStudentDetailException extends DomainException
{
    public function __construct()
    {
        parent::__construct(409, 'El usuario ya tiene detalles de estudiante asignados', ErrorCode::USER_ALREADY_HAVE_STUDENT_DETAIL);
    }
}
