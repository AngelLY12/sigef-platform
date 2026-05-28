<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class PaymentAlreadyExistsException extends DomainException
{
    public function __construct()
    {
        parent::__construct(409, "El concepto ya fue pagado por el usuario.", ErrorCode::PAYMENT_ALREADY_EXISTS);
    }
}
