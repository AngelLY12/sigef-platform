<?php

namespace App\Core\Domain\Enum\PaymentConcept;

enum PaymentConceptTimeScope: string
{
    case ONLY_ACTIVE = 'only_active';
    case INCLUDE_EXPIRED = 'include_expired';
}
