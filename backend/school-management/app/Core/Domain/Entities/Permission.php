<?php

namespace App\Core\Domain\Entities;

/**
 * @OA\Schema(
 *     schema="Permission",
 *     type="object",
 *     title="Permission",
 *     description="Representa un permiso dentro del sistema.",
 *     @OA\Property(property="id", type="integer", example=1, description="Identificador único del permiso."),
 *     @OA\Property(property="name", type="string", example="edit users", description="Nombre del permiso."),
 *     @OA\Property(property="type", type="string", example="model", description="Tipo de permiso."),
 * )
 */
class Permission
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $type,
    )
    {}
}
