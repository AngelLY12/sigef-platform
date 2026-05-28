<?php

namespace App\Core\Application\DTO\Response\User;

/**
 * @OA\Schema(
 *     schema="UsersFinancialSummary",
 *     type="object",
 *     title="UsersFinancialSummary",
 *     description="Resumen financiero de usuarios",
 *     @OA\Property(
 *         property="totalStudents",
 *         type="integer",
 *         description="Número total de estudiantes registrados",
 *         example=500
 *     ),
 *     @OA\Property(
 *         property="totalApplicants",
 *         type="integer",
 *         description="Número total de solicitantes",
 *         example=120
 *     )
 * )
 */
class UsersFinancialSummary
{
    public function __construct(
        public readonly int $totalStudents,
        public readonly int $totalApplicants
    ){}

}
