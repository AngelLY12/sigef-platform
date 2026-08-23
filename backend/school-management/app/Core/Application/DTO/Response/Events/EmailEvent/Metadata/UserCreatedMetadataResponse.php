<?php

namespace App\Core\Application\DTO\Response\Events\EmailEvent\Metadata;

use App\Core\Domain\ValueObjects\EmailEvent\UserCreatedEmailMetadata;

/**
 * @OA\Schema(
 *     schema="UserCreatedMetadataResponse",
 *     type="object",
 *     required={"recipientName"},
 *     @OA\Property(
 *         property="recipientName",
 *         type="string",
 *         description="Nombre del destinatario",
 *         example="Juan Carlos"
 *     )
 * )
 */
final readonly class UserCreatedMetadataResponse implements EmailEventMetadataResponse
{
    public function __construct(
        public string $recipientName,
    )
    {
    }

    public static function create(UserCreatedEmailMetadata $data): self
    {
        return new self(
            recipientName: $data->recipientName,
        );
    }

}
