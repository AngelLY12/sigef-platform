<?php

namespace App\Core\Application\DTO\Response\General;

/**
 * @OA\Schema(
 *     schema="PermissionsUpdatedToUserResponse",
 *     type="object",
 *     description="Respuesta con los permisos actualizados de un usuario",
 *     @OA\Property(property="userId", type="integer", description="ID del usuario", example=4),
 *     @OA\Property(
 *         property="fullName",
 *         type="string",
 *         description="Nombre completos del usuario afectados",
 *         example="Juan Perez"
 *     ),
 *
 *     @OA\Property(
 *          property="permissions",
 *          type="object",
 *          description="Lista de permisos actualizados (añadidos o removidos)",
 *          @OA\Property(property="added", type="array", @OA\Items(type="string"), example={"view.students"}),
 *          @OA\Property(property="removed", type="array", @OA\Items(type="string"), example={"create.student"})
 *      ),
 *
 * )
 */
class PermissionsUpdatedToUserResponse
{
    public function __construct(
        public readonly int $userId,
        public readonly string $fullName,
        public readonly array $permissions
    ){}

}
