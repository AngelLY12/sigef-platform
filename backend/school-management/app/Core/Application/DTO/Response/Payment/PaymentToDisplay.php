<?php

namespace App\Core\Application\DTO\Response\Payment;

/**
 * @OA\Schema(
 *     schema="PaymentToDisplay",
 *     type="object",
 *     @OA\Property(property="id", type="integer", description="ID interno del pago", example=123),
 *     @OA\Property(property="concept_name", type="string", description="Concepto del pago", example="Pago de inscripción"),
 *     @OA\Property(property="amount", type="string", description="Monto total del concepto", example="1500.00"),
 *     @OA\Property(property="status", type="string", description="Estado del pago", example="completed"),
 *     @OA\Property(
 *         property="created_at_human",
 *         type="string",
 *         description="Fecha de creación en formato humano relativo (generado con diffForHumans)",
 *         example="hace 2 días"
 *     ),
 *     @OA\Property(property="has_pending_amount", type="boolean", description="Indica si hay monto pendiente de pago", example=false),
 *     @OA\Property(
 *         property="balance",
 *         type="string",
 *         nullable=true,
 *         description="Saldo pendiente o sobrante. Positivo = saldo a favor, Negativo = pendiente por pagar, 'N/A' = pagado completo",
 *         example="N/A"
 *     ),
 *     @OA\Property(
 *         property="payment_method_details",
 *         type="object",
 *         nullable=true,
 *         description="Detalles del método de pago utilizado (objeto JSON)",
 *         @OA\Property(property="type", type="string", example="card"),
 *         @OA\Property(property="brand", type="string", example="visa"),
 *         @OA\Property(property="last4", type="string", example="4242")
 *     ),
 *     @OA\Property(property="amount_received", type="string", nullable=true, description="Monto efectivamente recibido", example="1500.00"),
 *     @OA\Property(property="reference", type="string", nullable=true, description="ID de referencia del pago (ej: payment_intent_id de Stripe)", example="pi_123456789"),
 *     @OA\Property(property="url", type="string", nullable=true, description="URL del recibo o comprobante de pago", example="https://example.com/receipt/123")
 * )
 */
class PaymentToDisplay
{
    public function __construct(
        public readonly int $id,
        public readonly string $concept_name,
        public readonly string $amount,
        public readonly string $status,
        public readonly string $created_at_human,
        public readonly bool $has_pending_amount,
        public readonly ?string $balance,
        public readonly ?array $payment_method_details,
        public readonly ?string $amount_received,
        public readonly ?string $reference,
        public readonly ?string $url,
    ){}

}
