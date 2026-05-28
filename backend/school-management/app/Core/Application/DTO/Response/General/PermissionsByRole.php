<?php

namespace App\Core\Application\DTO\Response\General;

/**
 * @OA\Schema(
 *     schema="PermissionsByRole",
 *     type="object",
 *     @OA\Property(property="role", type="string", example="student"),
 *     @OA\Property(property="usersCount", type="integer", example=120),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="object"),
 *         nullable=true,
 *         description="Lista de permisos de los usuarios")
 * )
 */
class PermissionsByRole
{
    public function __construct(
        public readonly string $role,
        public readonly int $usersCount,
        public readonly array $permissions
    )
    {
    }

}
