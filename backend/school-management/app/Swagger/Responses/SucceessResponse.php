<?php

namespace App\Swagger\Responses;

/**
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     description="Formato estándar de respuesta exitosa",
 *     @OA\Property(
 *         property="success",
 *         type="boolean",
 *         example=true,
 *         description="Indica que la operación fue exitosa"
 *     ),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         nullable=true,
 *         example="Operación completada exitosamente",
 *         description="Mensaje descriptivo opcional del resultado"
 *     ),
 *     @OA\Property(
 *         property="data",
 *         nullable=true,
 *         description="Datos de respuesta (estructura variable según el endpoint)",
 *         oneOf={
 *             @OA\Schema(type="object"),
 *             @OA\Schema(type="array", @OA\Items(type="object")),
 *             @OA\Schema(type="string"),
 *             @OA\Schema(type="number"),
 *             @OA\Schema(type="boolean"),
 *             @OA\Schema(type="null")
 *         }
 *     )
 * )
 */
class SucceessResponse
{

}
