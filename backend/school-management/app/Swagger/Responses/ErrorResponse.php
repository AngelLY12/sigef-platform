<?php

namespace App\Swagger\Responses;

/**
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     description="Formato estándar de respuesta de error",
 *     @OA\Property(
 *         property="success",
 *         type="boolean",
 *         example=false,
 *         description="Indica que la operación falló"
 *     ),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         nullable=true,
 *         example="Error descriptivo para el usuario",
 *         description="Mensaje de error principal"
 *     ),
 *     @OA\Property(
 *         property="error_code",
 *         type="string",
 *         nullable=true,
 *         example="VALIDATION_ERROR",
 *         description="Código de error estandarizado para programática"
 *     ),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         nullable=true,
 *         additionalProperties={
 *             "type": "array",
 *             "items": {"type": "string"}
 *         },
 *         example={
 *             "email": {"El campo email es requerido", "El email debe ser válido"},
 *             "password": {"La contraseña debe tener al menos 8 caracteres"}
 *         },
 *         description="Errores de validación detallados por campo (solo para errores 422)"
 *     )
 * )
 */
class ErrorResponse
{

}
