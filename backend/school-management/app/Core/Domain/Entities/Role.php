<?php

namespace App\Core\Domain\Entities;

/**
 * @OA\Schema(
 *     schema="Role",
 *     type="object",
 *     title="Role",
 *     description="Representa un rol dentro del sistema.",
 *     @OA\Property(property="id", type="integer", example=2, description="Identificador único del rol."),
 *     @OA\Property(property="name", type="string", example="Administrator", description="Nombre del rol.")
 * )
 */
class Role
{
    public function __construct(
        public readonly int $id,
        public readonly string $name
    )
    {}
}
