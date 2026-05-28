<?php

namespace App\Core\Application\DTO\Response\User;

/**
 * @OA\Schema(
 *     schema="UserAuthResponse",
 *     type="object",
 *     description="Representa un usuario del sistema",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="curp", type="string", example="PEMJ950615HDFRZN09"),
 *     @OA\Property(property="name", type="string", example="Juan"),
 *     @OA\Property(property="last_name", type="string", example="Pérez"),
 *     @OA\Property(property="email", type="string", format="email", example="juan.perez@example.com"),
 *     @OA\Property(property="password", type="string", example="hashed_password"),
 *     @OA\Property(property="phone_number", type="string", example="+5215512345678"),
 *     @OA\Property(property="status", ref="#/components/schemas/UserStatus", nullable=true, example="activo"),
 *     @OA\Property(property="registration_date", type="string", format="date-time", nullable=true, example="2024-01-15T12:34:56Z"),
 *     @OA\Property(property="emailVerifiedAt", type="string", description="Indica si el correo del usuario ya ha sido verificado y cuando", example ="2024-01-15T12:34:56Z"),
 *     @OA\Property(property="birthdate", type="string", format="date", nullable=true, example="1995-06-15"),
 *     @OA\Property(property="gender", ref="#/components/schemas/UserGender", nullable=true, example="male"),
 *     @OA\Property(property="address", type="array", nullable=true, @OA\Items(type="string"), example={"Calle Falsa 123", "Colonia Centro"}),
 *     @OA\Property(property="stripe_customer_id", type="string", nullable=true, example="cus_ABC123XYZ"),
 *     @OA\Property(property="blood_type", ref="#/components/schemas/UserBloodType", nullable=true, example="O+"),
 * )
 */
class UserAuthResponse
{

    public function __construct(
        public readonly int $id,
        public readonly string $curp,
        public readonly string $name,
        public readonly string $last_name,
        public readonly string $email,
        public readonly string $phone_number,
        public readonly string $status,
        public readonly string $registration_date,
        public readonly ?string $emailVerifiedAt = null,
        public readonly ?string $birthdate = null,
        public readonly ?string $gender = null,
        public readonly ?array $address = null,
        public readonly ?string $blood_type = null,
        public readonly ?string $stripe_customer_id = null,
    ){}
}
