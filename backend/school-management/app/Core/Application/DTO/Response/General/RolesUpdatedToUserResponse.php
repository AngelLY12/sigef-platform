<?php

namespace App\Core\Application\DTO\Response\General;

/**
 * @OA\Schema(
 *     schema="RolesUpdatedToUserResponse",
 *     type="object",
 *     description="Respuesta con los roles actualizados de un usuario",
 *     @OA\Property(property="userId", type="integer", description="ID del usuario", example=4),
 *     @OA\Property(
 *         property="fullName",
 *         type="string",
 *         description="Nombre completos del usuario afectados",
 *         example="Juan Perez"
 *     ),
 *
 *     @OA\Property(
 *         property="roles",
 *         type="object",
 *         description="Roles agregados y removidos",
 *         @OA\Property(property="added", type="array", @OA\Items(type="string"), example={"student"}),
 *         @OA\Property(property="removed", type="array", @OA\Items(type="string"), example={"guest"})
 *     ),
 *
 * )
 */
class RolesUpdatedToUserResponse
{
    public function __construct(
        public readonly int $userId,
        public readonly string $fullName,
        public readonly array $roles
    ){}

}
