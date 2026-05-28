<?php

namespace App\Exceptions\NotAllowed;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class PromotionNotAllowedException extends DomainException
{
    public function __construct($allowed)
    {
        parent::__construct(403, "La promoción solo se puede ejecutar en los meses permitidos: " . implode(', ',$allowed),ErrorCode::PROMOTION_NOT_ALLOWED);
    }

}
