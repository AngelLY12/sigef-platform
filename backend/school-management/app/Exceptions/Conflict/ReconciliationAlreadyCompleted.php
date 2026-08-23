<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ReconciliationAlreadyCompleted extends DomainException
{
    public function __construct(string $message = "Pago reconciliado con anterioridad.")
    {
        parent::__construct(409, $message, ErrorCode::PAYMENT_ALREADY_RECONCILED);
    }

}
