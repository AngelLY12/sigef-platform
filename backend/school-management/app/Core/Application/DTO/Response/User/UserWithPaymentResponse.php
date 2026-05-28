<?php

namespace App\Core\Application\DTO\Response\User;

/**
 * @OA\Schema(
 *     schema="UserWithPaymentResponse",
 *     type="object",
 *     @OA\Property(property="id", type="integer", nullable=true, description="ID del usuario", example=1),
 *     @OA\Property(property="fullName", type="string", nullable=true, description="Nombre completo del usuario", example="Juan Pérez"),
 *     @OA\Property(property="concept", type="string", nullable=true, description="Nombre del concepto asociado al pago", example="Pago de inscripción"),
 *     @OA\Property(property="amount", type="string", nullable=true, description="Monto del pago", example="1500.00")
 * )
 */
class UserWithPaymentResponse{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $fullName,
        public readonly ?string $concept,
        public readonly ?string $amount
    ) {}
}
