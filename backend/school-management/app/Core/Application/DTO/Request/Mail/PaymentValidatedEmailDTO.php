<?php

namespace App\Core\Application\DTO\Request\Mail;

class PaymentValidatedEmailDTO
{
        public function __construct(
        public readonly string $recipientName,
        public readonly string $recipientEmail,
        public readonly string $concept_name,
        public readonly string $amount,
        public readonly string $amount_received,
        public readonly string $status,
        public readonly array $payment_method_detail,
        public readonly ?string $payment_intent_id = null,
        public readonly?string $url = null
    ) {}

}
