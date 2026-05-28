<?php

namespace App\Core\Application\DTO\Response\User;

/**
 * @OA\Schema(
 *     schema="UserChangedStatusResponse",
 *     type="object",
 *     description="Respuesta después de cambiar el estado de uno o más usuarios",
 *
 *     @OA\Property(
 *         property="newStatus",
 *         type="string",
 *         description="Nuevo estado asignado a los usuarios",
 *         example="activo"
 *     ),
 *     @OA\Property(
 *         property="totalUpdated",
 *         type="integer",
 *         description="Número total de usuarios actualizados",
 *         example=5
 *     )
 * )
 */
class UserChangedStatusResponse
{
    public function __construct(
        public readonly ?string $newStatus,
        public readonly int $totalUpdated
    )
    {

    }
}
