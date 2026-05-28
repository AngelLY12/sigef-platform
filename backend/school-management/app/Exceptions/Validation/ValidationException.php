<?php

namespace App\Exceptions\Validation;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ValidationException extends DomainException
{
    public function __construct(string $message, int $code = 422,)
    {
        parent::__construct($code, $message, ErrorCode::VALIDATION_ERROR);
    }
}

