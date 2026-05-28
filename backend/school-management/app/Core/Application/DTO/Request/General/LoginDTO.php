<?php

namespace App\Core\Application\DTO\Request\General;

/**
 * @OA\Schema(
 *     schema="LoginDTO",
 *     type="object",
 *     description="Datos necesarios para iniciar sesión",
 *     required={"email","password"},
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         example="usuario@example.com"
 *     ),
 *     @OA\Property(
 *         property="password",
 *         type="string",
 *         format="password",
 *         example="secret123"
 *     )
 * )
 */
class LoginDTO{
    public function __construct(
        public readonly string $email,
        public readonly string $password
    )
    {
    }
}
