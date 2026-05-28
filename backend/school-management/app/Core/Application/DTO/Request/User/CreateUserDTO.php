<?php

namespace App\Core\Application\DTO\Request\User;

use App\Core\Domain\Enum\User\UserBloodType;
use App\Core\Domain\Enum\User\UserGender;
use App\Core\Domain\Enum\User\UserStatus;
use Carbon\Carbon;

/**
 * @OA\Schema(
 *     schema="CreateUserDTO",
 *     type="object",
 *     description="Datos para crear un nuevo usuario",
 *     required={"name","last_name","email","password","phone_number","curp"},
 *     @OA\Property(property="name", type="string", example="Juan", description="Nombre del usuario"),
 *     @OA\Property(property="last_name", type="string", example="Pérez", description="Apellido del usuario"),
 *     @OA\Property(property="email", type="string", format="email", example="juan.perez@example.com", description="Correo electrónico del usuario"),
 *     @OA\Property(property="password", type="string", format="password", example="Password123!", description="Contraseña del usuario"),
 *     @OA\Property(property="phone_number", type="string", example="+5215512345678", description="Número de teléfono"),
 *     @OA\Property(property="birthdate", type="string", format="date", nullable=true, example="1995-04-23", description="Fecha de nacimiento"),
 *     @OA\Property(property="gender", ref="#/components/schemas/UserGender", nullable=true, example="male", description="Género del usuario"),
 *     @OA\Property(property="curp", type="string", example="PEPJ950423HDFRRL09", description="CURP del usuario"),
 *     @OA\Property(property="address", type="array", nullable=true, @OA\Items(type="string"), description="Dirección del usuario"),
 *     @OA\Property(property="blood_type", ref="#/components/schemas/UserBloodType", nullable=true, example="O+", description="Tipo de sangre"),
 *     @OA\Property(property="registration_date", type="string", format="date-time", nullable=true, example="2025-11-04T19:00:00Z", description="Fecha de registro"),
 *     @OA\Property(property="status", ref="#/components/schemas/UserStatus", nullable=true, example="activo", description="Estado del usuario")
 * )
 */
class CreateUserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $last_name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $phone_number,
        public readonly string $curp,
        public readonly ?Carbon $birthdate = null,
        public readonly ?UserGender $gender = null,
        public readonly ?array $address = null,
        public readonly ?UserBloodType $blood_type = null,
        public readonly ?Carbon $registration_date = null,
        public readonly ?UserStatus $status = null,
    )
    {

    }
}
