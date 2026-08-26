<?php

namespace App\Core\Application\DTO\Request\Mail;

use App\Core\Domain\ValueObjects\Payment\Stripe\RequiredActionDetails;

class RequiresActionEmailDTO
{
    public function __construct(
        public readonly string $recipientName,
        public readonly string $recipientEmail,
        public readonly string $amount,
        public readonly RequiredActionDetails $requiredActionDetails,
    )
    {

    }

}

