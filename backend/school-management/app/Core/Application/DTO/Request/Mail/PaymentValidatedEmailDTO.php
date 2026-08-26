<?php

namespace App\Core\Application\DTO\Request\Mail;

use App\Core\Domain\ValueObjects\Payment\PaymentMethodDetails\PaymentMethodDetails;

class PaymentValidatedEmailDTO
{
        public function __construct(
        public readonly string $recipientName,
        public readonly string $recipientEmail,
        public readonly string $concept_name,
        public readonly string $amount,
        public readonly string $amount_received,
        public readonly string $status,
        public readonly ?PaymentMethodDetails $payment_method_detail,
        public readonly ?string $payment_intent_id = null,
        public readonly ?string $url = null
    ) {}

}
