<?php

namespace App\Core\Application\DTO\Response\Events\ReconcileEvent;

use App\Core\Application\DTO\Response\Events\ReconcileEvent\Metadata\ReconciliationEventMetadataResponse;
use App\Core\Application\Mappers\ReconciliationEventMetadataMapper;
use App\Core\Domain\Entities\ReconciliationEvent;

/**
 * @OA\Schema(
 *     schema="ReconcileEventByIdResponse",
 *     type="object",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         nullable=true,
 *         description="ID del evento de reconciliación",
 *         example=12
 *     ),
 *     @OA\Property(
 *         property="paymentId",
 *         type="integer",
 *         nullable=true,
 *         description="ID del pago asociado",
 *         example=14
 *     ),
 *     @OA\Property(
 *         property="outcome",
 *         type="string",
 *         nullable=true,
 *         description="Resultado de la reconciliación",
 *         example="corrected"
 *     ),
 *     @OA\Property(
 *         property="outcomeLabel",
 *         type="string",
 *         nullable=true,
 *         description="Nombre legible del resultado",
 *         example="Corregido"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         description="Estado del evento de reconciliación",
 *         example="completed"
 *     ),
 *     @OA\Property(
 *         property="statusLabel",
 *         type="string",
 *         description="Nombre legible del estado",
 *         example="Completado"
 *     ),
 *     @OA\Property(
 *         property="sourceType",
 *         type="string",
 *         description="Tipo de fuente que originó la reconciliación",
 *         example="stripe"
 *     ),
 *     @OA\Property(
 *         property="sourceTypeLabel",
 *         type="string",
 *         description="Nombre legible del tipo de fuente",
 *         example="Stripe"
 *     ),
 *     @OA\Property(
 *         property="sourceId",
 *         type="string",
 *         description="Identificador de la fuente",
 *         example="ch_3U6JrCCDJnKApcPA0"
 *     ),
 *     @OA\Property(
 *         property="errorMessage",
 *         type="string",
 *         nullable=true,
 *         description="Mensaje de error durante la reconciliación",
 *         example="Payment amount mismatch"
 *     ),
 *     @OA\Property(
 *      property="metadata",
 *      nullable=true,
 *      description="Metadata específica del resultado de la reconciliación",
 *      oneOf={
 *          @OA\Schema(ref="#/components/schemas/ReconciliationCorrectedMetadataResponse"),
 *          @OA\Schema(ref="#/components/schemas/ReconciliationMatchedMetadataResponse")
 *      }
 *      ),
 *     @OA\Property(
 *         property="startedAt",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Fecha de inicio de la reconciliación",
 *         example="2026-08-20T10:30:00Z"
 *     ),
 *     @OA\Property(
 *         property="completedAt",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Fecha de finalización de la reconciliación",
 *         example="2026-08-20T10:30:02Z"
 *     ),
 *     @OA\Property(
 *         property="failedAt",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Fecha en que falló la reconciliación",
 *         example="2026-08-20T10:30:02Z"
 *     ),
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
 *         example="2026-08-20T10:30:02Z"
 *     )
 * )
 */
final readonly class ReconcileEventByIdResponse
{
    public function __construct(
        public ?int $id,
        public ?int $paymentId,
        public ?string $outcome,
        public ?string $outcomeLabel,
        public string $status,
        public string $statusLabel,
        public string $sourceType,
        public string $sourceTypeLabel,
        public string $sourceId,
        public ?string $errorMessage,
        public ?ReconciliationEventMetadataResponse $metadata,
        public ?string $startedAt,
        public ?string $completedAt,
        public ?string $failedAt,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function create(ReconciliationEvent $event): self
    {
        return new self(
            id: $event->id,
            paymentId: $event->paymentId,
            outcome: $event->outcome->value,
            outcomeLabel: $event->outcome->label(),
            status: $event->status->value,
            statusLabel: $event->status->label(),
            sourceType: $event->sourceType->value,
            sourceTypeLabel: $event->sourceType->label(),
            sourceId: $event->sourceId,
            errorMessage: $event->errorMessage,
            metadata: ReconciliationEventMetadataMapper::toResponse(outcome: $event->outcome, metadata: $event->metadata),
            startedAt: $event->startedAt,
            completedAt: $event->completedAt,
            failedAt: $event->failedAt,
            createdAt: $event->createdAt,
            updatedAt: $event->updatedAt,
        );
    }

}
