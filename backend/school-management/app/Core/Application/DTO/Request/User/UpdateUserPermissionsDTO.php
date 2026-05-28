<?php

namespace App\Core\Application\DTO\Request\User;


/**
 * @OA\Schema(
 *     schema="UpdateUserPermissionsDTO",
 *     type="object",
 *     @OA\Property(
 *         property="curps",
 *         type="array",
 *         description="Lista de CURPs de los usuarios a actualizar",
 *         @OA\Items(type="string"),
 *         example={"XAXX010101HNEXXXA","XEXX010101HNEXXXB"}
 *     ),
 *     @OA\Property(
 *         property="role",
 *         type="string",
 *         description="Cadena de texto que representa el role a quienes se aplicaran los cambios",
 *         example={"student"}
 *     ),
 *     @OA\Property(
 *         property="permissionsToAdd",
 *         type="array",
 *         description="Lista de permisos a agregar a los usuarios",
 *         @OA\Items(type="string"),
 *         example={"find user","find concept"}
 *     ),
 *     @OA\Property(
 *         property="permissionsToRemove",
 *         type="array",
 *         description="Lista de permisos a remover de los usuarios",
 *         @OA\Items(type="string"),
 *         example={"delete payment"}
 *     )
 * )
 */

class UpdateUserPermissionsDTO{
    public function __construct(
        public readonly ?array $curps =[],
        public readonly ?string $role = null,
        public readonly ?array $permissionsToAdd = [],
        public readonly ?array $permissionsToRemove = []
    )
    {
    }

    public function toArray(): array
    {
        return [
            'curps' => $this->curps ?? [],
            'role' => $this->role ?? null,
            'permissionsToAdd' => $this->permissionsToAdd ?? [],
            'permissionsToRemove' => $this->permissionsToRemove ?? []
        ];
    }
}
