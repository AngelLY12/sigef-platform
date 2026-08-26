<?php

namespace App\Exceptions\NotFound;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class PaymentNotFountException extends DomainException
{
    public function __construct(string $message = 'El pago solicitado no fue encontrado.')
    {
        parent::__construct(404, $message, ErrorCode::PAYMENT_NOT_FOUND);
    }
}
