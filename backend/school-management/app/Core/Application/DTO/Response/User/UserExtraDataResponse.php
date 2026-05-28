<?php

namespace App\Core\Application\DTO\Response\User;


use App\Core\Application\DTO\Response\StudentDetail\StudentDetailDTO;


/**
 * @OA\Schema(
 *     schema="UserExtraDataResponse",
 *     description="Respuesta con información adicional detallada del usuario",
 *     type="object",
 *     @OA\Property(
 *          property="userId",
 *          type="integer",
 *          description="ID del usuario",
 *          example=45
 *      ),
 *     @OA\Property(
 *         property="basicInfo",
 *         type="object",
 *         description="Información básica adicional del usuario",
 *         @OA\Property(
 *             property="phone_number",
 *             type="string",
 *             description="Número de teléfono",
 *             example="+5215512345678"
 *         ),
 *     @OA\Property(
 *          property="birthdate",
 *          type="string",
 *          format="date",
 *          description="Fecha de nacimiento en formato YYYY-MM-DD",
 *          example="1995-03-15"
 *      ),
 *      @OA\Property(
 *          property="age",
 *          type="integer",
 *          description="Edad calculada a partir de la fecha de nacimiento",
 *          example=28
 *      ),
 *
 *         @OA\Property(
 *             property="address",
 *             type="string",
 *             description="Dirección del usuario",
 *             example="Calle Principal #123, Col. Centro, CDMX"
 *         ),
 *         @OA\Property(
 *             property="blood_type",
 *             type="string",
 *             description="Tipo de sangre",
 *             example="O+"
 *         ),
 *     @OA\Property(
 *          property="registration_date",
 *          type="string",
 *          format="date",
 *          description="Fecha de registro del usuario en formato YYYY-MM-DD",
 *          example="2023-01-15"
 *          )
 *     ),
 *     @OA\Property(
 *         property="roles",
 *         type="array",
 *         description="Roles asignados al usuario",
 *         @OA\Items(type="string", example="student")
 *     ),
 *     @OA\Property(
 *         property="permissions",
 *         type="array",
 *         description="Permisos directos del usuario",
 *         @OA\Items(type="string", example="view_grades")
 *     ),
 *     @OA\Property(
 *         property="studentDetail",
 *         ref="#/components/schemas/StudentDetailDTO",
 *         description="Información detallada del estudiante (solo si es estudiante)",
 *         nullable=true
 *     )
 * )
 */
class UserExtraDataResponse
{
    public function __construct(
        public readonly int $userId,
        public readonly array $basicInfo,
        public readonly array $roles,
        public readonly array $permissions,
        public readonly ?StudentDetailDTO $studentDetail,
    ){}

}
