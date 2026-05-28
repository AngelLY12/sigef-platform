<?php

namespace App\Core\Application\DTO\Request\Mail;

class NewPaymentConceptEmailDTO
{
    public function __construct(
        public readonly string $recipientName,
        public readonly string $recipientEmail,
        public readonly string $concept_name,
        public readonly string $amount,
        public readonly string $end_date,
        public readonly string $start_date,
        public readonly bool $isDisable,
    )
    {

    }

}
