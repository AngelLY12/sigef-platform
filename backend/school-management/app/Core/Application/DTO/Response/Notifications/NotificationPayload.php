<?php

namespace App\Core\Application\DTO\Response\Notifications;

use App\Core\Application\DTO\Response\Notifications\Contracts\NotificationMetadata;
use App\Core\Domain\Enum\Notification\NotificationSeverity;
use App\Core\Domain\Enum\Notification\NotificationType;

/**
 * @OA\Schema(
 *     schema="NotificationPayload",
 *     type="object",
 *     required={"type","title","message","severity","metadata"},
 *
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         description="Tipo de notificación",
 *         example="import_finished"
 *     ),
 *
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         description="Título de la notificación",
 *         example="Importación finalizada"
 *     ),
 *
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         description="Mensaje principal",
 *         example="Importación de datos finalizada, a continuación verás un resumen."
 *     ),
 *
 *     @OA\Property(
 *         property="severity",
 *         type="string",
 *         enum={"info","success","warning","error"},
 *         example="success"
 *     ),
 *
 *     @OA\Property(
 *         property="metadata",
 *         type="object",
 *         description="Información específica según el tipo de notificación"
 *     )
 * )
 */
final readonly class NotificationPayload
{
    public function __construct(
        public NotificationType     $type,
        public string               $title,
        public string               $message,
        public NotificationSeverity $severity,
        public NotificationMetadata $metadata,
    ){}

    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'title' => $this->title,
            'message' => $this->message,
            'severity' => $this->severity->value,
            'metadata' => $this->metadata->toArray(),
        ];
    }

}
