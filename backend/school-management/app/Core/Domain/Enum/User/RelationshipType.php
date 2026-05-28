<?php

namespace App\Core\Domain\Enum\User;


/**
 * @OA\Schema(
 *     schema="RelationshipType",
 *     type="string",
 *     enum={"padre","madre","tutor","tutor_legal"},
 *     description="Tipo de relación entre padre y estudiante",
 *     example="padre"
 * )
 */
enum RelationshipType: string
{
    case PADRE = 'padre';
    case MADRE = 'madre';
    case TUTOR = 'tutor';
    case TUTOR_LEGAL = 'tutor_legal';
}
