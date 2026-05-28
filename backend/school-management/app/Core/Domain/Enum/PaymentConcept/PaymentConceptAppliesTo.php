<?php

namespace App\Core\Domain\Enum\PaymentConcept;

/**
 * @OA\Schema(
 *     schema="PaymentConceptAppliesTo",
 *     type="string",
 *     description="A quien aplica el concepto de pago",
 *     enum={"todos", "carrera", "semestre", "carrera_semestre", "estudiantes"},
 *     example="todos"
 * )
 */
enum PaymentConceptAppliesTo: string
{
    case TODOS = 'todos';
    case CARRERA = 'carrera';
    case SEMESTRE = 'semestre';
    case CARRERA_SEMESTRE = 'carrera_semestre';
    case ESTUDIANTES = 'estudiantes';
    case TAG = 'tag';
}
