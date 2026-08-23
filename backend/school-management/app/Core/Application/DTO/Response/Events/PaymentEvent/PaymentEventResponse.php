<?php

namespace App\Core\Application\DTO\Response\Events\PaymentEvent;


use App\Models\PaymentEvent;

/**
 * @OA\Schema(
 *     schema="PaymentEventResponse",
 *     type="object",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         example=125
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
 *         property="eventType",
 *         type="string",
 *         example="webhook_payment_intent_succeeded"
 *     ),
 *     @OA\Property(
 *         property="processed",
 *         type="boolean",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="createdAt",
 *         type="string",
 *         format="date-time",
 *         example="2026-08-15 14:35:21"
 *     )
 * )
 */
class PaymentEventResponse
{
    public function __construct(
        public int $id,
        public ?int $paymentId,
        public ?string $conceptName,
        public string $eventType,
        public bool $processed,
        public string $createdAt,
    ) {}

    public static function create(PaymentEvent $event): self
    {
        return new self(
            id: $event->id,
            paymentId: $event->payment_id,
            conceptName: $event->concept_name,
            eventType: $event->event_type?->label(),
            processed: $event->processed,
            createdAt: $event->created_at->format('Y-m-d H:i:s'),
        );
    }

}
