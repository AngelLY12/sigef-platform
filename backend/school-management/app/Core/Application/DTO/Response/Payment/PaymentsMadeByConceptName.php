<?php

namespace App\Core\Application\DTO\Response\Payment;

/**
 * @OA\Schema(
 *     schema="PaymentsMadeByConceptName",
 *     type="object",
 *     @OA\Property(property="concept_name", type="string", description="Concepto del pago", example="Pago de inscripción"),
 *     @OA\Property(property="amount_total", type="string", description="Monto total que debe recibir ese concepto", example="1500.00"),
 *     @OA\Property(property="amount_received_total", type="string", description="Monto total pagado del concepto", example="1500.00"),
 *     @OA\Property(property="first_payment_date", type="string", description="Primera fecha de pago del concepto", example="2025-11-04"),
 *     @OA\Property(property="last_payment_date", type="string", description="Última fecha de pago del concepto", example="2025-12-04"),
 *     @OA\Property(property="collection_rate", type="string", description="Tasa de recaudación del concepto", example="100.00"),
 * )
 */
class PaymentsMadeByConceptName
{
    public function __construct(
        public readonly string $concept_name,
        public readonly string $amount_total,
        public readonly string $amount_received_total,
        public readonly string $first_payment_date,
        public readonly string $last_payment_date,
        public readonly string $collection_rate
    ){}

}
