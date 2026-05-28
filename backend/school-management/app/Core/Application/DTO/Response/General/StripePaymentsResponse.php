<?php

namespace App\Core\Application\DTO\Response\General;

/**
 * @OA\Schema(
 *     schema="StripePaymentsResponse",
 *     type="object",
 *     @OA\Property(property="id", type="string", nullable=true, description="ID interno del pago en Stripe", example="pay_01HXXXXXX"),
 *     @OA\Property(property="payment_intent_id", type="string", nullable=true, description="ID del Payment Intent en Stripe", example="pi_01HXXXXXX"),
 *     @OA\Property(property="concept_name", type="string", nullable=true, description="Nombre del concepto de pago", example="Pago de inscripción"),
 *     @OA\Property(property="status", type="string", nullable=true, description="Estado del pago", example="succeeded"),
 *     @OA\Property(property="amount_total", type="string", nullable=true, description="Monto total del pago", example="1500.00"),
 *     @OA\Property(property="amount_received", type="string", nullable=true, description="Monto total recibido", example="1500.00"),
 *     @OA\Property(property="created", type="string", nullable=true, description="Fecha de creación del pago en formato timestamp", example="2025-11-04T18:30:00Z"),
 *     @OA\Property(property="receipt_url", type="string", nullable=true, description="Comprobante del alumno", example="https://recipient_url")
 * )
 */
class StripePaymentsResponse
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $payment_intent_id,
        public readonly ?string $concept_name,
        public readonly ?string $status,
        public readonly ?string $amount_total,
        public readonly ?string $amount_received,
        public readonly ?string $created,
        public readonly ?string $receipt_url,
    )
    {}
}
