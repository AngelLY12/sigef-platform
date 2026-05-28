<?php

namespace App\Core\Domain\Enum\User;

/**
 * @OA\Schema(
 *     schema="UserGender",
 *     type="string",
 *     description="Generos válidos de un usuario",
 *     enum={"hombre", "mujer"},
 *     example="hombre"
 * )
 */
enum UserGender : string
{
    case HOMBRE = 'hombre';
    case MUJER = 'mujer';
}


