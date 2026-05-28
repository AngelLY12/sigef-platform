<?php

namespace App\Core\Application\DTO\Request\Mail;

class PaymentFailedEmailDTO
{
    public function __construct(
        public readonly string $recipientName,
        public readonly string $recipientEmail,
        public readonly string $concept_name,
        public readonly string $amount,
        public readonly string $error
    ) {}
}
