<?php

namespace App\Exceptions\ServerError;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class StripeGatewayException extends DomainException
{
    public function __construct(string $message, int $code = 500,)
    {
        parent::__construct($code, $message, ErrorCode::STRIPE_GATEWAY_ERROR);
    }
}
