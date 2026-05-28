<?php

namespace App\Exceptions\ServerError;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class PaymentReconciliationException extends DomainException
{
    public function __construct(string $details)
    {
        parent::__construct(500, "Error al reconciliar el pago: $details", ErrorCode::PAYMENT_RECONCILIATION_ERROR);
    }
}
