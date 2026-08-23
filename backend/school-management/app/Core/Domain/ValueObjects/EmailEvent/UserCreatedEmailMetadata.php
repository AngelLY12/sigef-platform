<?php

namespace App\Core\Domain\ValueObjects\EmailEvent;

use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\User\UserActorType;

final class UserCreatedEmailMetadata implements EmailEventMetadata
{
    public function __construct(
        public readonly string $emailTemplate,
        public readonly UserActorType $userActorType,
        public readonly int    $userId,
        public readonly string $recipientName,
    )
    {
    }

    public static function create(User $user, UserActorType $actorType): self
    {
        return new self(
            emailTemplate: 'users.created',
            userActorType: $actorType,
            userId: $user->id,
            recipientName: $user->fullName(),
        );
    }

    public static function createFromArray(array $data): self
    {
        return new self(
            emailTemplate: $data['email_template'],
            userActorType: UserActorType::from($data['user_actor_type']),
            userId: (int)$data['user_id'],
            recipientName: $data['recipientName']
        );
    }

    public function toArray(): array
    {
        return [
            'email_template' => $this->emailTemplate,
            'user_actor_type' => $this->userActorType->value,
            'user_id' => $this->userId,
            'recipientName' => $this->recipientName,
        ];
    }

}
