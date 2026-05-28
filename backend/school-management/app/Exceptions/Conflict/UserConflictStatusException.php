<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class UserConflictStatusException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct(409, $message, ErrorCode::USER_CONFLICT_STATUS);
    }
}
