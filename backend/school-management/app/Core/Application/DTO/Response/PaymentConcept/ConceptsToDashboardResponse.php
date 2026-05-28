<?php

namespace App\Core\Application\DTO\Response\PaymentConcept;


/**
 * @OA\Schema(
 *     schema="ConceptsToDashboardResponse",
 *     type="object",
 *     @OA\Property(property="id", type="integer", nullable=true, description="ID del concepto", example=1),
 *     @OA\Property(property="concept_name", type="string", nullable=true, description="Nombre del concepto de pago", example="Pago de inscripción"),
 *     @OA\Property(property="status", type="string", nullable=true, description="Estado del concepto", example="activo"),
 *     @OA\Property(property="amount", type="string", nullable=true, description="Monto del concepto", example="1500.00"),
 *     @OA\Property(property="applies_to", type="string", nullable=true, description="A quién aplica el concepto", example="todos"),
 *     @OA\Property(property="start_date", type="string", format="date", nullable=true, description="Fecha de inicio de validez", example="2025-11-01"),
 *     @OA\Property(property="end_date", type="string", format="date", nullable=true, description="Fecha de fin de validez", example="2025-12-01"),
 *     @OA\Property(
 *           property="expiration_human",
 *           type="string",
 *           nullable=true,
 *           description="Texto humano que indica el estado de expiración",
 *           example="Vence en 3 días"
 *       ),
 * )
 */
class ConceptsToDashboardResponse{

    public function __construct(
        public readonly ?int $id,
        public readonly ?string $concept_name,
        public readonly ?string $status,
        public readonly ?string $amount,
        public readonly ?string $applies_to,
        public readonly ?string $start_date,
        public readonly ?string $end_date,
        public readonly ?string $expiration_human
    ) {}

}
