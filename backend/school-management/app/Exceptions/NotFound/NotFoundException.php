<?php

namespace App\Exceptions\NotFound;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class NotFoundException extends DomainException
{
    public function __construct(string $message = 'No se encontro el recurso solicitado')
    {
        parent::__construct(404, $message, ErrorCode::GENERIC_NOT_FOUND);
    }

}
