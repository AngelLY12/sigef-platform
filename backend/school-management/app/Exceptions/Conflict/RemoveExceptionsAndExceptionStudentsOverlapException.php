<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class RemoveExceptionsAndExceptionStudentsOverlapException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct(409, $message, ErrorCode::REMOVE_EXCEPTIONS_AND_EXCEPTION_STUDENT_OVERLAP);
    }

}
