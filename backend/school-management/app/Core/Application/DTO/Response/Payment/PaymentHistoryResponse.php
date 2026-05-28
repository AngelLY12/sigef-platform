<?php

namespace App\Core\Application\DTO\Response\Payment;

/**
 * @OA\Schema(
 *     schema="PaymentHistoryResponse",
 *     type="object",
 *     @OA\Property(property="id", type="integer", nullable=true, description="ID interno del pago", example=123),
 *     @OA\Property(property="concept", type="string", nullable=true, description="Concepto del pago", example="Pago de inscripción"),
 *     @OA\Property(property="amount", type="string", nullable=true, description="Monto del pago", example="1500.00"),
 *     @OA\Property(property="amount_received", type="string", nullable=true, description="Monto del pago recibido", example="1500.00"),
 *     @OA\Property(property="status", type="string", nullable=true, description="Status del concepto del pago", example="paid"),
 *     @OA\Property(property="date", type="string", nullable=true, description="Fecha del pago", example="hace 2 dias")
 * )
 */
class PaymentHistoryResponse{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $concept,
        public readonly ?string $amount,
        public readonly ?string $amount_received,
        public readonly  ?String $status,
        public readonly ?string $date,
        public readonly ?string $date_iso
    ) {}
}
