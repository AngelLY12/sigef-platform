<?php

namespace App\Exceptions;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Excepción base para errores de dominio.
 */
abstract class DomainException extends HttpException
{
    protected ErrorCode $errorCode;
    /**
     * @param int $statusCode Código HTTP (401, 403, 422, etc.)
     * @param string $message Mensaje para el usuario
     * @param ErrorCode $errorCode Código único para el frontend
     */
    public function __construct(int $statusCode, string $message, ErrorCode $errorCode)
    {
        parent::__construct($statusCode, $message);
        $this->errorCode = $errorCode;

    }
    public function getErrorCode(): ErrorCode
    {
        return $this->errorCode;
    }
}

