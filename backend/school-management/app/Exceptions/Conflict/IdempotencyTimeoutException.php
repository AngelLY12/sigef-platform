<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class IdempotencyTimeoutException extends DomainException
{
    public function __construct(string $operation)
    {
        parent::__construct(409, "La operación {$operation} esta siendo procesada. Por favor, intentalo más tarde", ErrorCode::IDEMPOTENCY_TIMEOUT_EXCEPTION);
    }

}
