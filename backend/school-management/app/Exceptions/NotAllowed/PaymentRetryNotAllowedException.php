<?php

namespace App\Exceptions\NotAllowed;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class PaymentRetryNotAllowedException extends DomainException
{
    public function __construct($message)
    {
        parent::__construct(403, $message, ErrorCode::PAYMENT_RETRY_NOT_ALLOWED);
    }

}
