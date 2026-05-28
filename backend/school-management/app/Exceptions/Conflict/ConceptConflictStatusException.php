<?php

namespace App\Exceptions\Conflict;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ConceptConflictStatusException extends DomainException
{
    public function __construct(string $message)
    {
        parent::__construct(409, $message, ErrorCode::CONCEPT_CONFLICT_STATUS);
    }
}
