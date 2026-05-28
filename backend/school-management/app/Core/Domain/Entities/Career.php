<?php

namespace App\Core\Domain\Entities;

/**
 * @OA\Schema(
 *     schema="DomainCareer",
 *     type="object",
 *     @OA\Property(property="id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="career_name", type="string", example="Matematicas"),
 * )
 */
class Career
{
    public function __construct(
        public string $career_name,
        public ?int $id=null,
    ) {}
}
