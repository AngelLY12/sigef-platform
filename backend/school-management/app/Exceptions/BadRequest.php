<?php

namespace App\Exceptions;

use App\Core\Domain\Enum\Exceptions\ErrorCode;

class BadRequest extends DomainException
{
    public function __construct($message = "Ocurrio un error al procesar la solicitud")
    {
        parent::__construct(400,$message , ErrorCode::BAD_REQUEST);
    }

}
