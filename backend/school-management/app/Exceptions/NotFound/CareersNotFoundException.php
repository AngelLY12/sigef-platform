<?php

namespace App\Exceptions\NotFound;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class CareersNotFoundException extends DomainException
{
    public function __construct()
    {
        parent::__construct(404, 'La o las carreras no existen.', ErrorCode::CAREERS_NOT_FOUND);
    }
}
