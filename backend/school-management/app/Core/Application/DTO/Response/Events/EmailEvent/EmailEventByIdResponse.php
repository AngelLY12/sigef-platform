<?php

namespace App\Core\Application\DTO\Response\Events\EmailEvent;

use App\Core\Application\DTO\Response\Events\EmailEvent\Metadata\EmailEventMetadataResponse;
use App\Core\Application\Mappers\EmailEventMetadataMapper;
use App\Core\Domain\Entities\EmailEvent;

/**
 * @OA\Schema(
 *     schema="EmailEventByIdResponse",
 *     type="object",
 *     required={
 *         "id",
 *         "eventType",
 *         "eventTypeLabel",
 *         "recipientEmail",
 *         "status",
 *         "statusLabel",
 *         "sourceType",
 *         "sourceTypeLabel",
 *         "sourceId",
 *         "attemptCount"
 *     },
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="ID del evento de correo",
 *         example=15
 *     ),
 *     @OA\Property(
 *         property="userId",
 *         type="integer",
 *         nullable=true,
 *         description="ID del usuario asociado al evento",
 *         example=8
 *     ),
 *     @OA\Property(
 *         property="eventType",
 *         type="string",
 *         description="Tipo de evento de correo",
 *         example="payment_validated"
 *     ),
 *     @OA\Property(
 *         property="eventTypeLabel",
 *         type="string",
 *         description="Nombre legible del tipo de evento",
 *         example="Pago validado"
 *     ),
 *     @OA\Property(
 *         property="recipientEmail",
 *         type="string",
 *         format="email",
 *         description="Dirección de correo del destinatario",
 *         example="juan@example.com"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         description="Estado del evento de correo",
 *         example="sent"
 *     ),
 *     @OA\Property(
 *         property="statusLabel",
 *         type="string",
 *         description="Nombre legible del estado",
 *         example="Enviado"
 *     ),
 *     @OA\Property(
 *         property="sourceType",
 *         type="string",
 *         description="Tipo de entidad que originó el evento",
 *         example="payment"
 *     ),
 *     @OA\Property(
 *         property="sourceTypeLabel",
 *         type="string",
 *         description="Nombre legible del tipo de origen",
 *         example="Pago"
 *     ),
 *     @OA\Property(
 *         property="sourceId",
 *         type="string",
 *         description="Identificador de la entidad que originó el evento",
 *         example="14"
 *     ),
 *     @OA\Property(
 *         property="attemptCount",
 *         type="integer",
 *         description="Número de intentos realizados para procesar el evento",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="errorMessage",
 *         type="string",
 *         nullable=true,
 *         description="Mensaje de error del procesamiento",
 *         example="Connection timeout"
 *     ),
 *     @OA\Property(
 *         property="sentAt",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Fecha en que el correo fue enviado",
 *         example="2026-08-20T10:30:00Z"
 *     ),
 *     @OA\Property(
 *         property="deliveredAt",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Fecha en que el correo fue entregado",
 *         example="2026-08-20T10:30:05Z"
 *     ),
 *     @OA\Property(
 *         property="failedAt",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Fecha en que falló el envío",
 *         example="2026-08-20T10:30:10Z"
 *     ),
 *     @OA\Property(
 *      property="metadata",
 *      nullable=true,
 *      description="Metadata específica del tipo de evento",
 *      oneOf={
 *          @OA\Schema(ref="#/components/schemas/ConceptCreatedMetadataResponse"),
 *          @OA\Schema(ref="#/components/schemas/ConceptCriticalAmountAlertMetadataResponse"),
 *          @OA\Schema(ref="#/components/schemas/PaymentCreatedMetadataResponse"),
 *          @OA\Schema(ref="#/components/schemas/PaymentFailedMetadataResponse"),
 *          @OA\Schema(ref="#/components/schemas/PaymentRequiresActionMetadataResponse"),
 *          @OA\Schema(ref="#/components/schemas/PaymentValidatedMetadataResponse"),
 *          @OA\Schema(ref="#/components/schemas/UserCreatedMetadataResponse")
 *      }
 *      ),
 *     @OA\Property(
 *         property="createdAt",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Fecha de creación del evento",
 *         example="2026-08-20T10:29:55Z"
 *     ),
 *     @OA\Property(
 *         property="updatedAt",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Fecha de última actualización del evento",
 *         example="2026-08-20T10:30:05Z"
 *     )
 * )
 */
final readonly class EmailEventByIdResponse
{
    public function __construct(
        public int $id,
        public ?int $userId,
        public string $eventType,
        public string $eventTypeLabel,
        public string $recipientEmail,
        public string $status,
        public string $statusLabel,
        public string $sourceType,
        public string $sourceTypeLabel,
        public string $sourceId,
        public int $attemptCount,
        public ?string $errorMessage,
        public ?string $sentAt,
        public ?string $deliveredAt,
        public ?string $failedAt,
        public ?EmailEventMetadataResponse $metadata,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function create(EmailEvent $event): self
    {
        return new self(
            id: $event->id,
            userId: $event->userId,
            eventType: $event->eventType->value,
            eventTypeLabel: $event->eventType->label(),
            recipientEmail: $event->recipientEmail,
            status: $event->status->value,
            statusLabel: $event->status->label(),
            sourceType: $event->sourceType->value,
            sourceTypeLabel: $event->sourceType->label(),
            sourceId: $event->sourceId,
            attemptCount: $event->attemptCount,
            errorMessage: $event->errorMessage,
            sentAt: $event->sentAt?->format(DATE_ATOM),
            deliveredAt: $event->deliveredAt?->format(DATE_ATOM),
            failedAt: $event->failedAt?->format(DATE_ATOM),
            metadata: $event->metadata
                ? EmailEventMetadataMapper::toResponse(
                    eventType: $event->eventType,
                    eventMetadata: $event->metadata,
                )
                : null,
            createdAt: $event->createdAt?->format(DATE_ATOM),
            updatedAt: $event->updatedAt?->format(DATE_ATOM),
        );
    }

}
