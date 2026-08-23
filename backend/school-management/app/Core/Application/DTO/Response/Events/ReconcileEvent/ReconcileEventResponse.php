<?php

namespace App\Core\Application\DTO\Response\Events\ReconcileEvent;


use App\Models\PaymentReconciliationEvent;

/**
 * @OA\Schema(
 *     schema="ReconcileEventResponse",
 *     type="object",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         example=34
 *     ),
 *     @OA\Property(
 *         property="paymentId",
 *         type="integer",
 *         nullable=true,
 *         example=458
 *     ),
 *     @OA\Property(
 *         property="conceptName",
 *         type="string",
 *         nullable=true,
 *         example="Inscripción 2026"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         example="completed"
 *     ),
 *     @OA\Property(
 *         property="sourceType",
 *         type="string",
 *         nullable=true,
 *         example="manual"
 *     ),
 *     @OA\Property(
 *         property="sourceId",
 *         type="string",
 *         nullable=true,
 *         example="reconciliation_01JX8Y7K9M"
 *     ),
 *     @OA\Property(
 *         property="createdAt",
 *         type="string",
 *         format="date-time",
 *         example="2026-08-15 14:40:32"
 *     )
 * )
 */
class ReconcileEventResponse
{
    public function __construct(
        public int $id,
        public ?int $paymentId,
        public ?string $conceptName,
        public string $status,
        public ?string $sourceType,
        public ?string $sourceId,
        public string $createdAt,
    ) {}

    public static function create(PaymentReconciliationEvent $event): self
    {
        return new self(
            id: $event->id,
            paymentId: $event->payment_id,
            conceptName: $event->concept_name,
            status: $event->status?->label(),
            sourceType: $event->source_type?->label(),
            sourceId: $event->source_id,
            createdAt: $event->created_at->format('Y-m-d H:i:s'),
        );
    }

}
