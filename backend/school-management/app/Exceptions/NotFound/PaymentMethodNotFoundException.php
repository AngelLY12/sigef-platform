<?php

namespace App\Exceptions\NotFound;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class PaymentMethodNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct(404, 'Método de pago no encontrado.', ErrorCode::PAYMENT_METHOD_NOT_FOUND);
    }
}
