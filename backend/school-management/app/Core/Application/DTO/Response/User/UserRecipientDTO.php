<?php

namespace App\Core\Application\DTO\Response\User;

/**
 * @OA\Schema(
 *     schema="UserRecipientDTO",
 *     type="object",
 *     @OA\Property(property="id", type="integer", nullable=true, description="ID del usuario destinatario", example=1),
 *     @OA\Property(property="fullName", type="string", nullable=true, description="Nombre completo del destinatario", example="Juan Pérez"),
 *     @OA\Property(property="email", type="string", nullable=true, description="Correo electrónico del destinatario", example="juan.perez@example.com")
 * )
 */
class UserRecipientDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $fullName,
        public readonly ?string $email,
    ) {}
}
