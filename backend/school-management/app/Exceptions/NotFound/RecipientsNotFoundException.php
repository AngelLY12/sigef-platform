<?php

namespace App\Exceptions\NotFound;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class RecipientsNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct(404, 'No se encontraron destinatarios válidos para el concepto de pago.', ErrorCode::RECIPIENTS_NOT_FOUND);
    }
}
