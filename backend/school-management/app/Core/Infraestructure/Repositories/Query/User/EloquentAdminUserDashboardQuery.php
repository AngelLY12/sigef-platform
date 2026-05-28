<?php

namespace App\Core\Infraestructure\Repositories\Query\User;

use App\Core\Domain\Enum\User\UserRoles;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EloquentAdminUserDashboardQuery
{
    public static function getGlobalUserStats(bool $onlyThisYear): array
    {
        $startYear = now()->startOfYear();
        $endYear = now()->endOfYear();

        $stats = DB::table('users')
            ->when($onlyThisYear, fn($q) =>
            $q->whereBetween('users.created_at', [$startYear, $endYear])
            )
            ->selectRaw("
            COUNT(*) as total_users,

            SUM(status = 'activo') as active_users,
            SUM(status = 'baja') as inactive_users,
            SUM(status = 'baja-temporal') as temporal_inactive_users,
            SUM(status = 'eliminado') as deleted_users,

            SUM(created_at >= CURDATE()) as new_users_today,
            SUM(created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as new_users_this_week,
            SUM(created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as new_users_this_month
        ")
            ->first();

        $populationSummary = [
            'total_users' => (int)$stats->total_users,
            'active_users' => (int)$stats->active_users,
            'inactive_users' => (int)$stats->inactive_users,
            'temporal_inactive_users' => (int)$stats->temporal_inactive_users,
            'deleted_users' => (int)$stats->deleted_users,
        ];

        $recentActivity = [
            'new_users_today' => (int)$stats->new_users_today,
            'new_users_this_week' => (int)$stats->new_users_this_week,
            'new_users_this_month' => (int)$stats->new_users_this_month,
        ];

        return [$populationSummary, $recentActivity];
    }


    public static function getUsersByRoleSummary(): array
    {
        $rolesMap = DB::table('roles')->pluck('name', 'id');

        $rolesData = DB::table('model_has_roles')
            ->selectRaw('role_id, COUNT(model_id) as total')
            ->where('model_type', User::class)
            ->groupBy('role_id')
            ->pluck('total', 'role_id');

        $summary = [];

        foreach ($rolesMap as $roleId => $roleName) {
            $summary[$roleName] = (int)($rolesData[$roleId] ?? 0);
        }

        return $summary;
    }

    public static function getStudentAcademicAndAlerts(bool $onlyThisYear): array
    {
        $startYear = now()->startOfYear();
        $endYear = now()->endOfYear();

        $rolesMap = DB::table('roles')->pluck('name', 'id');
        $studentRoleId = array_search(UserRoles::STUDENT->value, $rolesMap->toArray());

        $studentStats = DB::table('users')
            ->join('model_has_roles', function ($join) use ($studentRoleId) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.role_id', $studentRoleId)
                    ->where('model_has_roles.model_type', User::class);
            })
            ->leftJoin('student_details', 'student_details.user_id', '=', 'users.id')
            ->when($onlyThisYear, fn($q) =>
            $q->whereBetween('users.created_at', [$startYear, $endYear])
            )
            ->selectRaw("
            COUNT(DISTINCT users.id) as students_total,

            SUM(student_details.user_id IS NULL) as students_without_student_details,
            SUM(student_details.user_id IS NOT NULL AND student_details.n_control IS NULL) as students_without_n_control,

            SUM(student_details.career_id IS NOT NULL) as students_with_career,
            SUM(student_details.career_id IS NULL) as students_without_career,

            SUM(student_details.semestre IS NULL) as students_without_semester,
            SUM(student_details.group IS NULL) as students_without_group
        ")
            ->first();

        $academicSummary = [
            'students_total' => (int)$studentStats->students_total,
            'students_with_career' => (int)$studentStats->students_with_career,
            'students_without_career' => (int)$studentStats->students_without_career,
            'students_without_semester' => (int)$studentStats->students_without_semester,
            'students_without_group' => (int)$studentStats->students_without_group,
        ];

        $systemAlerts = [
            'students_without_n_control' => (int)$studentStats->students_without_n_control,
            'students_without_student_details' => (int)$studentStats->students_without_student_details,
            'users_without_role' => self::countUsersWithoutRole($onlyThisYear),
        ];

        return [$academicSummary, $systemAlerts];
    }

    private static function countUsersWithoutRole(bool $onlyThisYear): int
    {
        return DB::table('users')
            ->leftJoin('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', User::class);
            })
            ->whereNull('model_has_roles.role_id')
            ->when($onlyThisYear, fn($q) =>
            $q->whereBetween('users.created_at', [now()->startOfYear(), now()->endOfYear()])
            )
            ->count();
    }

}
