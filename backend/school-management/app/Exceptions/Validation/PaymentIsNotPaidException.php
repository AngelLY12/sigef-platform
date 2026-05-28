<?php

namespace App\Exceptions\Validation;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class PaymentIsNotPaidException extends DomainException
{
    public function __construct(string $message = "El concepto aun no esta en estado terminal")
    {
        parent::__construct(422, $message, ErrorCode::PAYMENT_IS_NOT_PAID);
    }
}
