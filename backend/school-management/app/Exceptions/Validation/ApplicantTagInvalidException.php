<?php
namespace App\Exceptions\Validation;

use App\Core\Domain\Enum\Exceptions\ErrorCode;
use App\Exceptions\DomainException;

class ApplicantTagInvalidException extends DomainException
{
    public function __construct()
    {
        parent::__construct(422, 'El tag ingresado no es valido.', ErrorCode::APPLICANT_TAG_INVALID);
    }
}
