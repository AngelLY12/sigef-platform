<?php

namespace App\Core\Application\DTO\Request\Mail;

class SendParentInviteEmailDTO
{
    public function __construct(
        public readonly string $recipientName,
        public readonly string $recipientEmail,
        public readonly string $token,
    )
    {

    }

}
