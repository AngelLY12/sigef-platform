<?php

namespace App\Core\Application\DTO\Response\PaymentConcept;

/**
 * @OA\Schema(
 *     schema="ConceptNameAndAmountResponse",
 *     type="object",
 *     @OA\Property(property="userId", type="string", nullable=true, description="ID del usuario asociado al pago", example=34),
 *     @OA\Property(property="user_name", type="string", nullable=true, description="Nombre del usuario asociado al pago", example="Juan Pérez"),
 *     @OA\Property(property="n_control", type="string", nullable=true, description="Número de control del usuario asociado al pago", example="97763726"),
 *     @OA\Property(property="concept_name", type="string", nullable=true, description="Nombre del concepto de pago", example="Pago de inscripción"),
 *     @OA\Property(property="amount", type="string", nullable=true, description="Monto del pago", example="1500.00")
 * )
 */
class ConceptNameAndAmountResponse
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $user_name,
        public readonly ?string $n_control,
        public readonly ?string $concept_name,
        public readonly ?string $amount,
    )
    {
    }
}
