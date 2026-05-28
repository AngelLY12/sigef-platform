<?php

namespace App\Core\Domain\Entities;

/**
 * @OA\Schema(
 *     schema="DomainStudentDetail",
 *     type="object",
 *     description="Detalles del estudiante",
 *     @OA\Property(property="user_id", type="integer", example=123),
 *     @OA\Property(property="id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="career_id", type="integer", nullable=true, example=10),
 *     @OA\Property(property="n_control", type="string", nullable=true, example="20201234"),
 *     @OA\Property(property="semestre", type="integer", nullable=true, example=5),
 *     @OA\Property(property="group", type="string", nullable=true, example="A"),
 *     @OA\Property(property="workshop", type="string", nullable=true, example="Taller de programación")
 * )
 */
class StudentDetail{
    public function __construct(
        /** @var User */
        public int $user_id,
        public ?int $id=null,
        /** @var Career */
        public ?int $career_id = null,
        public ?string $n_control = null,
        public ?int $semestre = null,
        public ?string $group = null,
        public ?string $workshop = null,
    ) {}

    public function promote(): void {
        if ($this->semestre !== null) {
            $this->semestre++;
        }
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'career_id' => $this->career_id,
            'n_control' => $this->n_control,
            'semestre' => $this->semestre,
            'group' => $this->group,
            'workshop' => $this->workshop,
        ];
    }

}
