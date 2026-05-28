<?php
namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class UserAlreadyDeletedException extends DomainException
{
    public function __construct()
    {
        parent::__construct(409, 'El usuario ya fue eliminado', ErrorCode::USER_ALREADY_DELETED);
    }
}
