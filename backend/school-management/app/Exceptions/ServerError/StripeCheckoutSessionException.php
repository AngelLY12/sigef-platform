<?php

namespace App\Exceptions\ServerError;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class StripeCheckoutSessionException extends DomainException
{
    public function __construct()
    {
        parent::__construct(500, "Ocurrió un error al crear la sesión de pago en Stripe.", ErrorCode::STRIPE_CHECKOUT_SESSION_ERROR);
    }
}
