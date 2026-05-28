<?php

namespace App\Core\Application\DTO\Response\Parents;


/**
 * @OA\Schema(
 *     schema="ParentChildrenResponse",
 *     type="object",
 *     description="Respuesta del get que muestra los hijos del padre",
 *
 *     @OA\Property(
 *         property="parentId",
 *         type="integer",
 *         description="Id del parent",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="parentName",
 *         type="string",
 *         description="Nombre del parent",
 *         example="Juan Perez"
 *     ),
 *      @OA\Property(
 *         property="childrenData",
 *         type="array",
 *         description="Hijos del familiar",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="id", type="integer", example=3),
 *             @OA\Property(property="name", type="string", example="Jesus Perez"),
 *         )
 *     ),
 * )
 */
class ParentChildrenResponse
{
    public function __construct(
        public readonly int $parentId,
        public readonly string $parentName,
        public readonly array $childrenData,
    )
    {
    }
}
