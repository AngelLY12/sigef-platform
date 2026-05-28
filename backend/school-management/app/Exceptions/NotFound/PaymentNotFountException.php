<?php

namespace App\Exceptions\NotFound;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class PaymentNotFountException extends DomainException
{
    public function __construct()
    {
        parent::__construct(404, 'El pago solicitado no fue encontrado.', ErrorCode::PAYMENT_NOT_FOUND);
    }
}
