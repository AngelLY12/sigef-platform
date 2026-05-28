<?php

namespace App\Core\Application\DTO\Request\Mail;

class RequiresActionEmailDTO
{
    public function __construct(
        public readonly string $recipientName,
        public readonly string $recipientEmail,
        public readonly string $amount,
        public readonly array $next_action,
        public readonly array $payment_method_options
    )
    {

    }

}

