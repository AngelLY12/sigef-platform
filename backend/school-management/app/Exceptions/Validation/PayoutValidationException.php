<?php

namespace App\Exceptions\Validation;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class PayoutValidationException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct(422, $message, ErrorCode::PAYOUT_VALIDATION);
    }

}
