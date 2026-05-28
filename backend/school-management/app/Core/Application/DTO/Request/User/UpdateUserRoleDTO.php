<?php

namespace App\Core\Application\DTO\Request\User;

/**
 * @OA\Schema(
 *     schema="UpdateUserRoleDTO",
 *     type="object",
 *     required={"curps"},
 *     description="Datos para actualizar roles de múltiples usuarios",
 *     @OA\Property(
 *         property="curps",
 *         type="array",
 *         description="Lista de CURPs de los usuarios a actualizar",
 *         @OA\Items(type="string", example="PEPJ800101HDFRRN09")
 *     ),
 *     @OA\Property(
 *         property="rolesToAdd",
 *         type="array",
 *         description="Lista de roles que se añadirán a los usuarios",
 *         @OA\Items(type="string"),
 *         example={"student", "admin"}
 *     ),
 *     @OA\Property(
 *         property="rolesToRemove",
 *         type="array",
 *         description="Lista de roles que se eliminarán de los usuarios",
 *         @OA\Items(type="string"),
 *         example={"guest"}
 *     )
 * )
 */
class UpdateUserRoleDTO{
    public function __construct(
        public readonly array $curps,
        public readonly array $rolesToAdd,
        public readonly array $rolesToRemove
    )
    {
    }

    public function toArray(): array
    {
        return [
            'curps' => $this->curps,
            'rolesToAdd' => $this->rolesToAdd,
            'rolesToRemove' => $this->rolesToRemove,
        ];
    }
}
