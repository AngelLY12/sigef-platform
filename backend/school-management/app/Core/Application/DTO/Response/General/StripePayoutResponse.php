<?php

namespace App\Core\Application\DTO\Response\General;

/**
 * @OA\Schema(
 *     schema="StripePayoutResponse",
 *     type="object",
 *     description="Respuesta de creación de payout en Stripe",
 *     @OA\Property(
 *         property="success",
 *         type="boolean",
 *         example=true,
 *         description="Indica si el payout fue creado exitosamente"
 *     ),
 *     @OA\Property(
 *         property="payout_id",
 *         type="string",
 *         example="po_1PZ8ex2eZvKYlo2CQhxEXAMPLE",
 *         description="ID único del payout en Stripe (prefijo 'po_')"
 *     ),
 *     @OA\Property(
 *         property="amount",
 *         type="string",
 *         example="1500.50",
 *         description="Monto del payout en formato decimal con 2 decimales"
 *     ),
 *     @OA\Property(
 *         property="currency",
 *         type="string",
 *         example="mxn",
 *         description="Código de moneda ISO 4217 (mxn para pesos mexicanos)"
 *     ),
 *     @OA\Property(
 *         property="arrival_date",
 *         type="string",
 *         format="date",
 *         example="2024-01-15",
 *         description="Fecha estimada de llegada de los fondos en formato YYYY-MM-DD"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"pending", "in_transit", "paid", "failed", "canceled"},
 *         example="pending",
 *         description="Estado actual del payout en Stripe"
 *     ),
 *     @OA\Property(
 *         property="available_before_payout",
 *         type="string",
 *         example="1500.50",
 *         description="Balance disponible antes de crear el payout"
 *     )
 * )

 */
class StripePayoutResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $payout_id,
        public readonly string $amount,
        public readonly string $currency,
        public readonly string $arrival_date,
        public readonly string $status,
        public readonly string $available_before_payout
    ){}

    public static function fromArray(array $data): self
    {
        return new self(
            success: (bool) ($data['success'] ?? false),
            payout_id: (string) ($data['payout_id'] ?? ''),
            amount: (string) ($data['amount'] ?? '0.00'),
            currency: (string) ($data['currency'] ?? 'mxn'),
            arrival_date: (string) ($data['arrival_date'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            available_before_payout: (string) ($data['available_before_payout'] ?? '0.00')
        );
    }

}
