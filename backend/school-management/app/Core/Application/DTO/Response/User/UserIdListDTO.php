<?php

namespace App\Core\Application\DTO\Response\User;

/**
 * @OA\Schema(
 *     schema="UserIdListDTO",
 *     type="object",
 *     @OA\Property(property="userIds", type="array", nullable=true, description="Lista de IDs de usuario", @OA\Items(type="integer"), example={1,2,3})
 * )
 */
class UserIdListDTO{
    public function __construct(
        public readonly ?array $userIds
    ) {}
}
