<?php

namespace App\Core\Application\DTO\Request\StudentDetail;

/**
 * @OA\Schema(
 *     schema="CreateStudentDetailDTO",
 *     type="object",
 *     description="Datos para crear un detalle de estudiante",
 *     @OA\Property(property="user_id", type="integer", example=1, description="ID del usuario asociado al detalle"),
 *     @OA\Property(property="career_id", type="integer", nullable=true, example=2, description="ID de la carrera del estudiante"),
 *     @OA\Property(property="n_control", type="string", nullable=true, example="123456", description="Número de control del estudiante"),
 *     @OA\Property(property="semestre", type="integer", nullable=true, example=3, description="Semestre actual del estudiante"),
 *     @OA\Property(property="group", type="string", nullable=true, example="A", description="Grupo del estudiante"),
 *     @OA\Property(property="workshop", type="string", nullable=true, example="Taller de matemáticas", description="Taller asignado al estudiante")
 * )
 */
class CreateStudentDetailDTO{
        public function __construct(
        public int $user_id,
        public ?int $career_id = null,
        public ?string $n_control = null,
        public ?int $semestre = null,
        public ?string $group = null,
        public ?string $workshop = null,
    ) {}
}
