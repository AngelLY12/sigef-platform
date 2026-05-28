<?php

namespace App\Core\Application\DTO\Response\PaymentConcept;

/**
 * @OA\Schema(
 *     schema="PendingPaymentConceptsResponse",
 *     type="object",
 *     @OA\Property(property="id", type="integer", nullable=true, description="ID del concepto pendiente", example=1),
 *     @OA\Property(property="concept_name", type="string", nullable=true, description="Nombre del concepto pendiente", example="Pago de inscripción"),
 *     @OA\Property(property="description", type="string", nullable=true, description="Descripción del concepto", example="Pago correspondiente al semestre 2025-2"),
 *     @OA\Property(property="amount", type="string", nullable=true, description="Monto del concepto pendiente", example="1500.00"),
 *     @OA\Property(property="start_date", type="string", format="date", nullable=true, description="Fecha de inicio del concepto pendiente", example="2025-11-01"),
 *     @OA\Property(property="end_date", type="string", format="date", nullable=true, description="Fecha de finalización del concepto pendiente", example="2025-12-01"),
 *     @OA\Property(
 *          property="expiration_human",
 *          type="string",
 *          nullable=true,
 *          description="Texto humano que indica el estado de expiración",
 *          example="Vence en 3 días"
 *      ),
 *      @OA\Property(
 *          property="expiration_info",
 *          type="object",
 *          nullable=true,
 *          description="Información detallada sobre la expiración",
 *          @OA\Property(property="text", type="string", nullable=true, example="Vence en 3 días"),
 *          @OA\Property(property="days", type="integer", nullable=true, description="Días restantes (negativo si ya expiró)", example=3),
 *          @OA\Property(property="is_expired", type="boolean", example=false),
 *          @OA\Property(property="is_today", type="boolean", description="Si vence hoy", example=false),
 *          @OA\Property(property="urgency", type="string", description="Nivel de urgencia: expirado, critico, alto, medio, bajo", example="medio"),
 *          @OA\Property(property="date_formatted", type="string", description="Fecha formateada en español", example="1 de diciembre de 2025"),
 *          @OA\Property(property="date_short", type="string", description="Fecha en formato corto", example="01/12/2025")
 *      )
 * )
 */
class PendingPaymentConceptsResponse {
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $concept_name,
        public readonly ?string $description,
        public readonly ?string $amount,
        public readonly ?string $start_date,
        public readonly ?string $end_date,
        public readonly ?string $expiration_human,
        public readonly ?array $expiration_info
    ) {}
}

