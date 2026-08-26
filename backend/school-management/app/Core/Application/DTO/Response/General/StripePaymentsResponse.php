<?php

namespace App\Core\Application\DTO\Response\General;

use App\Core\Domain\ValueObjects\Payment\Stripe\PaymentStripeMetadata;

/**
 * @OA\Schema(
 *     schema="StripePaymentsResponse",
 *     type="object",
 *     @OA\Property(
 *          property="customer_name",
 *          type="string",
 *          nullable=true,
 *          description="Nombre del usuario",
 *          example="John Doe"
 *      ),
 *     @OA\Property(
 *         property="concept_name",
 *         type="string",
 *         nullable=true,
 *         description="Nombre del concepto de pago",
 *         example="Pago de inscripción"
 *     ),
 *     @OA\Property(
 *         property="payment_id",
 *         type="integer",
 *         nullable=true,
 *         description="ID interno del pago",
 *         example=14
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         nullable=true,
 *         description="ID interno del usuario",
 *         example=8
 *     ),
 *     @OA\Property(
 *         property="concept_id",
 *         type="integer",
 *         nullable=true,
 *         description="ID interno del concepto de pago",
 *         example=25
 *     ),
 *     @OA\Property(
 *         property="paid",
 *         type="boolean",
 *         description="Indica si el cobro fue pagado",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         nullable=true,
 *         description="Estado del cobro en Stripe",
 *         example="succeeded"
 *     ),
 *     @OA\Property(
 *         property="amount",
 *         type="string",
 *         nullable=true,
 *         description="Monto del cobro",
 *         example="5468.00"
 *     ),
 *     @OA\Property(
 *         property="amount_received",
 *         type="string",
 *         nullable=true,
 *         description="Monto recibido",
 *         example="5468.00"
 *     ),
 *     @OA\Property(
 *         property="created",
 *         type="string",
 *         nullable=true,
 *         description="Fecha de creación del cobro",
 *         example="2026-08-19 17:25:47"
 *     ),
 *     @OA\Property(
 *         property="receipt_url",
 *         type="string",
 *         nullable=true,
 *         description="URL del comprobante de Stripe",
 *         example="https://pay.stripe.com/receipts/..."
 *     ),
 *     @OA\Property(
 *         property="payment_method_type",
 *         type="string",
 *         nullable=true,
 *         description="Tipo de método de pago utilizado",
 *         example="oxxo"
 *     )
 * )
 */
class StripePaymentsResponse
{
    public function __construct(
        public readonly ?string $customer_name,
        public readonly ?string $concept_name,
        public readonly ?int $payment_id,
        public readonly ?int $user_id,
        public readonly ?int $concept_id,
        public readonly bool $paid,
        public readonly ?string $status,
        public readonly ?string $amount,
        public readonly ?string $amount_received,
        public readonly ?string $created,
        public readonly ?string $receipt_url,
        public readonly ?string $payment_method_type,
    ) {}
}
