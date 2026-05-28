<?php

namespace App\Core\Domain\Enum\User;

/**
 * @OA\Schema(
 *     schema="UserBloodType",
 *     type="string",
 *     description="Tipos de sangre válidos de un usuario",
 *     enum={"O+", "O-", "A+", "A-", "B+", "B-", "AB+", "AB-"},
 *     example="O+"
 * )
 */
enum UserBloodType: string
{
    case O_POSITIVE = 'O+';
    case O_NEGATIVE = 'O-';
    case A_POSITIVE = 'A+';
    case A_NEGATIVE = 'A-';
    case B_POSITIVE = 'B+';
    case B_NEGATIVE = 'B-';
    case AB_POSITIVE = 'AB+';
    case AB_NEGATIVE = 'AB-';


}

