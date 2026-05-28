<?php

namespace App\Exceptions\Validation;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class PaymentMethodNotSupportedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(422, "El método de pago no es soportado.", ErrorCode::PAYMENT_METHOD_NOT_SUPPORTED);
    }
}
