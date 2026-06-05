<?php

namespace App\Core\Application\DTO\Response\Notifications;

use App\Core\Application\DTO\Response\Notifications\Contracts\NotificationMetadata;

/**
 * @OA\Schema(
 *     schema="ImportNotificationData",
 *     type="object",
 *
 *     @OA\Property(
 *         property="error",
 *         type="string",
 *         nullable=true,
 *         example="No se pudo procesar el archivo CSV"
 *     ),
 *
 *     @OA\Property(
 *         property="details",
 *         type="string",
 *         nullable=true,
 *         example="100 filas procesadas, 2 errores encontrados"
 *     )
 * )
 */
final readonly class ImportNotificationDataDTO implements NotificationMetadata
{
    public function __construct(
        public ?string $error = null,
        public ?string $details = null
    ) {}
    public function toArray(): array
    {
        return array_filter([
            'error' => $this->error,
            'details' => $this->details,
        ], fn ($value) => $value !== null);
    }

}
