<?php

namespace App\Core\Application\DTO\Response\User;

/**
 * @OA\Schema(
 *     schema="UserDataResponse",
 *     type="object",
 *     @OA\Property(property="id", type="integer", nullable=true, description="ID del usuario", example=1),
 *     @OA\Property(property="fullName", type="string", nullable=true, description="Nombre completo del usuario", example="Juan Pérez"),
 *     @OA\Property(property="email", type="string", nullable=true, description="Correo electrónico del usuario", example="juan.perez@example.com"),
 *     @OA\Property(property="curp", type="string", nullable=true, description="CURP del usuario", example="PEPJ800101HDFRRN09"),
 *     @OA\Property(property="n_control", type="string", nullable=true, description="Número de control del estudiante", example="2025001")
 * )
 */
class UserDataResponse{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $fullName,
        public readonly ?string $email,
        public readonly ?string $curp,
        public readonly ?string $n_control
    ) {}
}
