<?php

namespace App\Core\Application\DTO\Response\PaymentConcept;

use App\Models\PaymentConcept;

/**
 * @OA\Schema(
 *     schema="ConceptToDisplay",
 *     type="object",
 *     description="Representa un concepto de pago",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="concept_name", type="string", example="Pago de inscripción"),
 *     @OA\Property(property="status", ref="#/components/schemas/PaymentConceptStatus", example="activo"),
 *     @OA\Property(property="start_date", type="string", format="date", example="2025-09-01"),
 *     @OA\Property(property="amount", type="string", example="1500.00"),
 *     @OA\Property(property="applies_to", type="string", example="todos"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Pago correspondiente al semestre 2025A"),
 *     @OA\Property(property="end_date", type="string", format="date", nullable=true, example="2025-12-31"),
 *     @OA\Property(property="deleted_at", type="string", nullable=true, format="date", example="2025-12-31"),
 *     @OA\Property(property="created_at_human", type="string",  nullable=true, example="hace 2 dias"),
 *     @OA\Property(property="updated_at_human", type="string", nullable=true, example="hace 2 dias"),
 *     @OA\Property(property="deleted_at_human", type="string", nullable=true, example="hace 2 dias"),
 *     @OA\Property(property="days_until_deletion", type="integer", nullable=true, example=2),
 *     @OA\Property(
 *           property="expiration_human",
 *           type="string",
 *           nullable=true,
 *           description="Texto humano que indica el estado de expiración",
 *           example="Vence en 3 días"
 *       ),
 *     @OA\Property(
 *           property="expiration_info",
 *           type="object",
 *           nullable=true,
 *           description="Información detallada sobre la expiración",
 *           @OA\Property(property="text", type="string", nullable=true, example="Vence en 3 días"),
 *           @OA\Property(property="days", type="integer", nullable=true, description="Días restantes (negativo si ya expiró)", example=3),
 *           @OA\Property(property="is_expired", type="boolean", example=false),
 *           @OA\Property(property="is_today", type="boolean", description="Si vence hoy", example=false),
 *           @OA\Property(property="urgency", type="string", description="Nivel de urgencia: expirado, critico, alto, medio, bajo", example="medio"),
 *           @OA\Property(property="date_formatted", type="string", description="Fecha formateada en español", example="1 de diciembre de 2025"),
 *           @OA\Property(property="date_short", type="string", description="Fecha en formato corto", example="01/12/2025")
 *       )
 * )
 */
class ConceptToDisplay
{
    public function __construct(
        public readonly int $id,
        public readonly string $concept_name,
        public readonly string $status,
        public readonly string $start_date,
        public readonly string $amount,
        public readonly string $applies_to,
        public readonly string $created_at_human,
        public readonly string $updated_at_human,
        public readonly array $expiration_info,
        public readonly ?string $expiration_human,
        public readonly ?string $deleted_at,
        public readonly ?string $deleted_at_human,
        public readonly ?int $days_until_deletion,
        public readonly ?string $description=null,
        public readonly ?string $end_date=null,

    )
    {
    }


}
