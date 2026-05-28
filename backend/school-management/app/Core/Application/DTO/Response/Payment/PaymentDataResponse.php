<?php

namespace App\Core\Application\DTO\Response\Payment;

/**
 * @OA\Schema(
 *     schema="PaymentDataResponse",
 *     type="object",
 *     @OA\Property(property="id", type="integer", nullable=true, description="ID interno del pago", example=123),
 *     @OA\Property(property="amount", type="string", nullable=true, description="Monto del pago", example="1500.00"),
 *     @OA\Property(property="amount_received", type="string", nullable=true, description="Monto del pago recibido", example="1500.00"),
 *     @OA\Property(property="status", type="string", nullable=true, description="Estado del pago", example="completed"),
 *     @OA\Property(property="payment_intent_id", type="string", nullable=true, description="ID del Payment Intent asociado", example="pi_01HXXXXXX")
 * )
 */
class PaymentDataResponse{
     public function __construct(
        public readonly ?int $id,
        public readonly ?string $amount,
        public readonly ?string $amount_received,
        public readonly ?string $status,
        public readonly ?string $payment_intent_id,
    ) {}
}
