<?php

namespace App\Exceptions\ServerError;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class PaymentNotificationException extends DomainException
{
    public function __construct(string $details)
    {
        parent::__construct(500, "Error al notificar al usuario: $details", ErrorCode::PAYMENT_NOTIFICATION_ERROR);
    }
}
