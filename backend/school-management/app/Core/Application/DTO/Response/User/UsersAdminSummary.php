<?php

namespace App\Core\Application\DTO\Response\User;

/**
 * @OA\Schema(
 *     schema="UsersAdminSummary",
 *     type="object",
 *     title="UsersAdminSummary",
 *     description="Resumen administrativo de usuarios para el dashboard",
 *     @OA\Property(
 *         property="populationSummary",
 *         type="object",
 *         description="Estadísticas generales de los usuarios",
 *         @OA\Property(property="total_users", type="integer", example=1200),
 *         @OA\Property(property="active_users", type="integer", example=900),
 *         @OA\Property(property="inactive_users", type="integer", example=200),
 *         @OA\Property(property="temporal_inactive_users", type="integer", example=50),
 *         @OA\Property(property="deleted_users", type="integer", example=50)
 *     ),
 *     @OA\Property(
 *         property="usersByRoleSummary",
 *         type="object",
 *         description="Cantidad de usuarios por cada rol",
 *         example={"Admin": 10, "Student": 500, "Teacher": 100}
 *     ),
 *     @OA\Property(
 *         property="academicSummary",
 *         type="object",
 *         description="Resumen académico de los estudiantes",
 *         @OA\Property(property="students_total", type="integer", example=500),
 *         @OA\Property(property="students_with_career", type="integer", example=450),
 *         @OA\Property(property="students_without_career", type="integer", example=50),
 *         @OA\Property(property="students_without_semester", type="integer", example=20),
 *         @OA\Property(property="students_without_group", type="integer", example=15)
 *     ),
 *     @OA\Property(
 *         property="systemAlerts",
 *         type="object",
 *         description="Alertas del sistema relacionadas con usuarios y estudiantes",
 *         @OA\Property(property="users_without_role", type="integer", example=5),
 *         @OA\Property(property="students_without_n_control", type="integer", example=30),
 *         @OA\Property(property="students_without_student_details", type="integer", example=10)
 *     ),
 *     @OA\Property(
 *         property="recentActivity",
 *         type="object",
 *         description="Actividad reciente de usuarios",
 *         @OA\Property(property="new_users_today", type="integer", example=3),
 *         @OA\Property(property="new_users_this_week", type="integer", example=20),
 *         @OA\Property(property="new_users_this_month", type="integer", example=70)
 *     )
 * )
 */
class UsersAdminSummary
{
    public function __construct(
        public readonly array $populationSummary,
        public readonly array $usersByRoleSummary,
        public readonly array $academicSummary,
        public readonly array $systemAlerts,
        public readonly array $recentActivity,

)
    {
    }

}
