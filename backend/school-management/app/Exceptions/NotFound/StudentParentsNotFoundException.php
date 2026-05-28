<?php

namespace App\Exceptions\NotFound;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class StudentParentsNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct(404, 'No se encontraron parientes relacionados a este usuario.', ErrorCode::STUDENT_PARENTS_NOT_FOUND);
    }

}
