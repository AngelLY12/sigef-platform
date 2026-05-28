<?php

namespace App\Exceptions\Validation;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class RequiredForAppliesToException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct(422, $message, ErrorCode::REQUIRED_FOR_APPLIES_TO);
    }
}
