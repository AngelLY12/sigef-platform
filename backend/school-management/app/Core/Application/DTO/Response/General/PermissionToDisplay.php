<?php

namespace App\Core\Application\DTO\Response\General;

/**
 * @OA\Schema(
 *     schema="PermissionToDisplay",
 *     title="Permission To Display",
 *     description="Permission data formatted for UI display purposes",
 *     @OA\Property(
 *          property="id",
 *          type="integer",
 *          example=1,
 *          description="Unique permission ID"
 *      ),
 *      @OA\Property(
 *          property="name",
 *          type="string",
 *          example="users.create",
 *          description="Internal permission name"
 *      ),
 *      @OA\Property(
 *          property="type",
 *          type="string",
 *          example="model",
 *          description="Permission type"
 *      ),
 *      @OA\Property(
 *          property="label",
 *          type="string",
 *          example="Crear usuarios",
 *          description="Label para lectura"
 *      ),
 *      @OA\Property(
 *          property="group",
 *          type="string",
 *          example="Usuarios",
 *          description="UI grupo o categoria del permiso"
 *      )
 *
 * )
 *
 */
class PermissionToDisplay
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?string $type,
        public readonly ?string $label,
        public readonly ?string $group
    ){}

}
