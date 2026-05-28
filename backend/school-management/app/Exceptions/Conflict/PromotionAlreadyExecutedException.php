<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class PromotionAlreadyExecutedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(409, "Ya se ejecuto la promoción este mes.", ErrorCode::PROMOTION_ALREADY_EXECUTED);
    }
}
