<?php

namespace App\Swagger\Responses;

/**
 * @OA\Schema(
 *     schema="StudentControlNumber",
 *     description="Resultado de búsqueda de número de control",
 *     type="object",
 *     @OA\Property(property="id", type="integer", description="ID del usuario", example=123),
 *     @OA\Property(property="n_control", type="string", description="Número de control del estudiante", example="20230001"),
 *     @OA\Property(property="name", type="string", description="Nombre completo del estudiante", example="Juan Pérez López"),
 *     @OA\Property(property="text", type="string", description="Texto formateado para display", example="20230001 - Juan Pérez López")
 * )
 */
class StudentControlNumber
{

}
