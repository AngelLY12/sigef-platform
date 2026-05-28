<?php

namespace App\Core\Application\DTO\Response\Parents;

/**
 * @OA\Schema(
 *     schema="StudentParentsResponse",
 *     type="object",
 *     description="Respuesta del get que muestra los hijos del padre",
 *
 *     @OA\Property(
 *         property="studentId",
 *         type="integer",
 *         description="Id del estudiante",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="studentName",
 *         type="string",
 *         description="Nombre del estudiante",
 *         example="Juan Perez"
 *     ),
 *      @OA\Property(
 *         property="parentsData",
 *         type="array",
 *         description="Familiares del estudiante",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=3),
 *             @OA\Property(property="name", type="string", example="Jesus Perez"),
 *         )
 *     ),
 * )
 */
class StudentParentsResponse
{

    public function __construct(
        public readonly int $studentId,
        public readonly string $studentName,
        public readonly array $parentsData
    ){}

}
