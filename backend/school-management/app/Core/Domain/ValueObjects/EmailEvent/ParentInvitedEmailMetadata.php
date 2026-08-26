<?php

namespace App\Core\Domain\ValueObjects\EmailEvent;

use App\Core\Domain\Entities\User;

final readonly class ParentInvitedEmailMetadata implements EmailEventMetadata
{
    public function __construct(
        public string $emailTemplate,
        public string $recipientName,
    ){}

    public static function create(User $user): self
    {
        return new self(
            emailTemplate: 'parents.invite',
           recipientName: $user->fullName(),
        );
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            emailTemplate: $data['email_template'],
            recipientName: $data['recipient_name'],
        );
    }

    public function toArray(): array
    {
        return [
            'email_template' => $this->emailTemplate,
            'recipient_name' => $this->recipientName,
        ];

    }

}
