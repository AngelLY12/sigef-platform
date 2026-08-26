<?php

namespace App\Core\Application\DTO\Response\Events\EmailEvent\Metadata;

use App\Core\Domain\ValueObjects\EmailEvent\ParentInvitedEmailMetadata;

final readonly class ParentInvitedMetadataResponse implements EmailEventMetadataResponse
{
    public function __construct(
        public string $recipientName,
    ) {}

    public static function create(ParentInvitedEmailMetadata $data): self
    {
        return new self(
            recipientName: $data->recipientName,
        );
    }

}
