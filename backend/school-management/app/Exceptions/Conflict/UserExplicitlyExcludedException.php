<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class UserExplicitlyExcludedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(409, 'Estas exluido de este concepto, no puedes pagarlo', ErrorCode::USER_EXPLICITLY_EXCLUDED);
    }

}
