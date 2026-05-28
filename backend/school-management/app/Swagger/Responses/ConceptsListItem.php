<?php

namespace App\Swagger\Responses;

/**
 * @OA\Schema(
 *     schema="ConceptsListItem",
 *     type="object",
 *     description="Item de concepto para listado",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="concept_name", type="string", example="Pago de inscripción"),
 *     @OA\Property(property="amount", type="string", example="1,500.00"),
 *     @OA\Property(property="description", type="string", example="Pago de inscripción para todos los estudiantes"),
 *     @OA\Property(property="status", type="string", example="active"),
 *     @OA\Property(property="applies_to", type="string", example="todos"),
 *     @OA\Property(
 *         property="expiration_human",
 *         type="string",
 *         nullable=true,
 *         description="Texto humano que indica estado de expiración",
 *         example="Vence en 3 días"
 *     ),
 *     @OA\Property(
 *          property="days_until_deletion",
 *          type="integer",
 *          nullable=true,
 *          description="Dias que faltan para ser eliminado",
 *          example=2
 *      ),
 *     @OA\Property(
 *         property="has_expiration",
 *         type="boolean",
 *         description="Indica si el concepto tiene fecha de expiración",
 *         example=true
 *     ),
 *     @OA\Property(
 *          property="is_deleted",
 *          type="boolean",
 *          description="Indica si el concepto fue marcado como eliminado",
 *          example=true
 *      )
 * )
 */
class ConceptsListItem
{

}
