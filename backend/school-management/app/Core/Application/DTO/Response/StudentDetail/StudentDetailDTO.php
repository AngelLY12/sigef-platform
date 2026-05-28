<?php

namespace App\Core\Application\DTO\Response\StudentDetail;

/**
 * @OA\Schema(
 *     schema="StudentDetailDTO",
 *     description="Información detallada del estudiante",
 *     type="object",
 *     @OA\Property(
 *         property="nControl",
 *         type="string",
 *         description="Número de control del estudiante",
 *         example="191240001",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="semestre",
 *         type="integer",
 *         description="Semestre actual del estudiante",
 *         example=8,
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="group",
 *         type="string",
 *         description="Grupo al que pertenece el estudiante",
 *         example="8A",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *          property="workshop",
 *          type="string",
 *          description="Taller al que pertenece el estudiante",
 *          example="Dibujo",
 *          nullable=true
 *      ),
 *     @OA\Property(
 *         property="careerName",
 *         type="string",
 *         description="Nombre de la carrera del estudiante",
 *         example="Ingeniería en Sistemas Computacionales",
 *         nullable=true
 *     )
 * )
 */
class StudentDetailDTO
{
    public function __construct(
        public readonly ?string $nControl,
        public readonly ?int $semestre,
        public readonly ?string $group,
        public readonly ?String $workshop,
        public readonly ?string $careerName

    ){}

}
