<?php

namespace App\Core\Application\DTO\Response\PaymentConcept;

/**
 * @OA\Schema(
 *     schema="ConceptChangeStatusResponse",
 *     type="object",
 *     title="Respuesta de cambio de estado de concepto de pago",
 *     description="Respuesta detallada al cambiar el estado de un concepto de pago",
 *     required={"conceptData", "message", "changes", "updatedAt"},
 *     @OA\Property(
 *         property="conceptData",
 *         type="object",
 *         description="Datos actualizados del concepto de pago",
 *         required={"id", "concept_name", "status", "amount", "start_date", "end_date", "applies_to"},
 *         @OA\Property(property="id", type="integer", example=123, description="ID del concepto"),
 *         @OA\Property(property="concept_name", type="string", example="Matrícula 2024", description="Nombre del concepto"),
 *         @OA\Property(
 *             property="status",
 *             type="string",
 *             enum={"activo", "inactivo", "finalizado", "cancelado", "pendiente"},
 *             example="finalizado",
 *             description="Nuevo estado del concepto"
 *         ),
 *         @OA\Property(property="amount", type="number", format="float", example=1500.00, description="Monto del concepto"),
 *         @OA\Property(property="start_date", type="string", format="date", example="2024-01-15", description="Fecha de inicio"),
 *         @OA\Property(property="end_date", type="string", format="date", example="2024-12-31", description="Fecha de fin"),
 *         @OA\Property(
 *             property="applies_to",
 *             type="string",
 *             enum={"todos", "carrera", "semestre", "estudiantes", "carrera_semestre", "tag"},
 *             example="carrera",
 *             description="A quién aplica el concepto"
 *         )
 *     ),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Concepto 'Matrícula 2024' finalizado exitosamente",
 *         description="Mensaje descriptivo del resultado"
 *     ),
 *     @OA\Property(
 *         property="changes",
 *         type="array",
 *         description="Lista de cambios realizados",
 *         @OA\Items(
 *             type="object",
 *             required={"field", "old", "new", "type", "transition_type"},
 *             @OA\Property(property="field", type="string", example="status", description="Campo modificado"),
 *             @OA\Property(property="old", type="string", example="activo", description="Valor anterior"),
 *             @OA\Property(property="new", type="string", example="finalizado", description="Valor nuevo"),
 *             @OA\Property(property="type", type="string", example="status_change", description="Tipo de cambio"),
 *             @OA\Property(
 *                 property="transition_type",
 *                 type="string",
 *                 enum={"activate", "deactivate", "finalize", "cancel"},
 *                 example="finalize",
 *                 description="Tipo de transición realizada"
 *             )
 *         ),
 *         example={{"field": "status", "old": "activo", "new": "finalizado", "type": "status_change", "transition_type": "finalize"}}
 *     ),
 *     @OA\Property(
 *         property="updatedAt",
 *         type="string",
 *         format="date-time",
 *         example="2024-01-15T10:30:00Z",
 *         description="Fecha y hora de la actualización"
 *     )
 * )
 */
class ConceptChangeStatusResponse
{
    public function __construct(
        public readonly array $conceptData,
        public readonly string $updatedAt,
        public readonly ?array $changes =[],
        public readonly ?string $message,
    ){}

}
