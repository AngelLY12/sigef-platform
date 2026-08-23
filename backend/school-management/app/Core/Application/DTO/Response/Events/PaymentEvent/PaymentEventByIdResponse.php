<?php

namespace App\Core\Application\DTO\Response\Events\PaymentEvent;

use App\Core\Application\DTO\Response\Events\PaymentEvent\Metadata\PaymentEventMetadataResponse;
use App\Core\Application\Mappers\PaymentEventMetadataMapper;
use App\Core\Domain\Entities\PaymentEvent;

/**
 * @OA\Schema(
 *     schema="PaymentEventByIdResponse",
 *     type="object",
 *     required={
 *         "id",
 *         "paymentId",
 *         "eventType",
 *         "eventTypeLabel",
 *         "processed",
 *         "retryCount"
 *     },
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="ID del evento de pago",
 *         example=25
 *     ),
 *     @OA\Property(
 *         property="paymentId",
 *         type="integer",
 *         description="ID del pago asociado",
 *         example=14
 *     ),
 *     @OA\Property(
 *         property="stripeEventId",
 *         type="string",
 *         nullable=true,
 *         description="ID del evento generado por Stripe",
 *         example="evt_1U6JrCCDJnKApcPA0"
 *     ),
 *     @OA\Property(
 *         property="stripePaymentIntentId",
 *         type="string",
 *         nullable=true,
 *         description="ID del Payment Intent de Stripe",
 *         example="pi_3U6JrCCDJnKApcPA0"
 *     ),
 *     @OA\Property(
 *         property="stripeSessionId",
 *         type="string",
 *         nullable=true,
 *         description="ID de la Checkout Session de Stripe",
 *         example="cs_test_a1b2c3"
 *     ),
 *     @OA\Property(
 *         property="eventType",
 *         type="string",
 *         description="Tipo de evento de pago",
 *         example="payment_intent_succeeded"
 *     ),
 *     @OA\Property(
 *         property="eventTypeLabel",
 *         type="string",
 *         description="Nombre legible del tipo de evento",
 *         example="Payment Intent completado"
 *     ),
 *     @OA\Property(
 *      property="metadata",
 *      nullable=true,
 *      description="Metadata específica del tipo de evento de Stripe",
 *      oneOf={
 *          @OA\Schema(ref="#/components/schemas/ChargeSucceededMetadataResponse"),
 *          @OA\Schema(ref="#/components/schemas/CheckoutSessionMetadataResponse"),
 *          @OA\Schema(ref="#/components/schemas/PaymentIntentCancelledMetadataResponse"),
 *          @OA\Schema(ref="#/components/schemas/PaymentIntentFailedMetadataResponse"),
 *          @OA\Schema(ref="#/components/schemas/PaymentIntentRequiresActionMetadataResponse"),
 *          @OA\Schema(ref="#/components/schemas/PaymentIntentSucceededMetadataResponse")
 *      }
 *      ),
 *     @OA\Property(
 *         property="amountReceived",
 *         type="string",
 *         nullable=true,
 *         description="Monto recibido durante el evento",
 *         example="5468.00"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         nullable=true,
 *         description="Estado del pago",
 *         example="succeeded"
 *     ),
 *     @OA\Property(
 *         property="statusLabel",
 *         type="string",
 *         nullable=true,
 *         description="Nombre legible del estado",
 *         example="Completado"
 *     ),
 *     @OA\Property(
 *         property="processed",
 *         type="boolean",
 *         description="Indica si el evento ya fue procesado",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="errorMessage",
 *         type="string",
 *         nullable=true,
 *         description="Mensaje de error durante el procesamiento",
 *         example="Unable to process payment event"
 *     ),
 *     @OA\Property(
 *         property="retryCount",
 *         type="integer",
 *         description="Número de reintentos realizados",
 *         example=0
 *     ),
 *     @OA\Property(
 *         property="processedAt",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         description="Fecha en que el evento fue procesado",
 *         example="2026-08-20T10:30:00Z"
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
 *         example="2026-08-20T10:30:05Z"
 *     )
 * )
 */
final readonly class PaymentEventByIdResponse
{
    public function __construct(
        public int $id,
        public int $paymentId,
        public ?string $stripeEventId,
        public ?string $stripePaymentIntentId,
        public ?string $stripeSessionId,
        public string $eventType,
        public string $eventTypeLabel,
        public ?PaymentEventMetadataResponse $metadata,
        public ?string $amountReceived,
        public ?string $status,
        public ?string $statusLabel,
        public bool $processed,
        public ?string $errorMessage,
        public int $retryCount,
        public ?string $processedAt,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function create(PaymentEvent $event): self
    {
        return new self(
            id: $event->id,
            paymentId: $event->paymentId,
            stripeEventId: $event->stripeEventId,
            stripePaymentIntentId: $event->stripePaymentIntentId,
            stripeSessionId: $event->stripeSessionId,
            eventType: $event->eventType->value,
            eventTypeLabel: $event->eventType->label(),
            metadata: $event->metadata ? PaymentEventMetadataMapper::toResponse(paymentEventType: $event->eventType,metadata: $event->metadata) : null,
            amountReceived: $event->amountReceived,
            status: $event->status?->value,
            statusLabel: $event->status?->label(),
            processed: $event->processed,
            errorMessage: $event->errorMessage,
            retryCount: $event->retryCount,
            processedAt: $event->processedAt?->format(DATE_ATOM),
            createdAt: $event->createdAt?->format(DATE_ATOM),
            updatedAt: $event->updatedAt?->format(DATE_ATOM),
        );
    }
}
