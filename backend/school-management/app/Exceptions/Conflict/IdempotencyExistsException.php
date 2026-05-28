<?php

namespace App\Exceptions\Conflict;


use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class IdempotencyExistsException extends DomainException
{
    protected string $key;
    public function __construct(string $key, string $message = "Operación ya fue ejecutada.")
    {
        $this->key = $key;
        parent::__construct(409, $message, ErrorCode::IDEMPOTENCY_EXISTS_EXCEPTION);
    }

    public function getKey(): string
    {
        return $this->key;
    }

}
