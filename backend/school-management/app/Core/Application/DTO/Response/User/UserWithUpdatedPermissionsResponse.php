<?php

namespace App\Core\Application\DTO\Response\User;
/**
 * @OA\Schema(
 *     schema="UserWithUpdatedPermissionsResponse",
 *     type="object",
 *     description="Respuesta de una operación masiva de actualización de permisos sobre usuarios",
 *     @OA\Property(
 *         property="summary",
 *         type="object",
 *         description="Resumen global de la operación",
 *         @OA\Property(property="totalFound", type="integer", example=30, description="Total de usuarios encontrados"),
 *         @OA\Property(property="totalUpdated", type="integer", example=20, description="Total de usuarios actualizados"),
 *         @OA\Property(property="totalUnchanged", type="integer", example=5, description="Total de usuarios sin cambios"),
 *         @OA\Property(property="totalFailed", type="integer", example=5, description="Total de usuarios que fallaron"),
 *         @OA\Property(
 *             property="operations",
 *             type="object",
 *             description="Operaciones realizadas",
 *             @OA\Property(property="total_permissions_removed", type="integer", example=15, description="Total de permisos removidos"),
 *             @OA\Property(property="total_permissions_added", type="integer", example=10, description="Total de permisos agregados"),
 *             @OA\Property(property="total_roles_processed", type="integer", example=3, description="Total de roles procesados")
 *         )
 *     ),
 *     @OA\Property(
 *         property="users",
 *         type="object",
 *         description="Detalle de usuarios procesados",
 *         @OA\Property(
 *             property="processed_users_id",
 *             type="array",
 *             @OA\Items(type="integer"),
 *             example={1, 2, 3},
 *             description="IDs de primeros 10 usuarios procesados exitosamente"
 *         ),
 *      @OA\Property(
 *              property="affected_users_id",
 *              type="array",
 *              @OA\Items(type="integer"),
 *              example={1, 2, 3},
 *              description="IDs de primeros 10 usuarios procesados exitosamente"
 *          ),
 *         @OA\Property(
 *             property="failed_users_id",
 *             type="array",
 *             @OA\Items(type="integer"),
 *             example={4, 5},
 *             description="IDs de usuarios que fallaron"
 *         ),
 *         @OA\Property(
 *             property="unchanged_users_id",
 *             type="array",
 *             @OA\Items(type="integer"),
 *             example={6, 7},
 *             description="IDs de usuarios que no tuvieron cambios"
 *         )
 *     ),
 *     @OA\Property(
 *         property="permissionsProcessed",
 *         type="object",
 *         description="Permisos procesados durante la operación",
 *         @OA\Property(
 *             property="processed_added",
 *             type="array",
 *             @OA\Items(type="string"),
 *             example={"view.students", "edit.students"}
 *         ),
 *         @OA\Property(
 *             property="processed_removed",
 *             type="array",
 *             @OA\Items(type="string"),
 *             example={"create.student"}
 *         )
 *     )
 * )
 */
class UserWithUpdatedPermissionsResponse
{
    public function __construct(
        public readonly ?array $summary,
        public readonly ?array $users,
        public readonly ?array $permissionsProcessed,
    )
    {
    }
}
