<?php

namespace App\Core\Application\DTO\Response\PaymentMethod;

/**
 * @OA\Schema(
 *     schema="DisplayPaymentMethodResponse",
 *     type="object",
 *     @OA\Property(property="id", type="integer", nullable=true, description="ID del método de pago", example=1),
 *     @OA\Property(property="brand", type="string", nullable=true, description="Marca de la tarjeta", example="Visa"),
 *     @OA\Property(property="masked_card", type="string", nullable=true, description="Número de tarjeta enmascarado", example="**** **** **** 4242"),
 *     @OA\Property(property="expiration_date", type="string", nullable=true, description="Fecha de expiración de la tarjeta", example="12/25"),
 *     @OA\Property(property="status", type="string", nullable=true, description="Estado del método de pago", example="active")
 * )
 */
class DisplayPaymentMethodResponse
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $brand,
        public readonly ?string $masked_card,
        public readonly ?string $expiration_date,
        public readonly ?string $status,
    ) {}
}


