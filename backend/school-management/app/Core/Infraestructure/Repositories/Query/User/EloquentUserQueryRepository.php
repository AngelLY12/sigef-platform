<?php

namespace App\Core\Infraestructure\Repositories\Query\User;

use App\Core\Application\DTO\Response\User\UserAuthResponse;
use App\Core\Application\DTO\Response\User\UserExtraDataResponse;
use App\Core\Application\DTO\Response\User\UserIdListDTO;
use App\Core\Application\DTO\Response\User\UsersAdminSummary;
use App\Core\Application\DTO\Response\User\UsersFinancialSummary;
use App\Core\Application\Mappers\UserMapper as MappersUserMapper;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Models\User as EloquentUser;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Infraestructure\Mappers\RolesAndPermissionMapper;
use App\Core\Infraestructure\Traits\HasPendingQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EloquentUserQueryRepository implements UserQueryRepInterface
{
    use HasPendingQuery;

    public function findById(int $userId): ?User
    {
        return optional(EloquentUser::with('roles')->find($userId),fn($eloquent)=>UserMapper::toDomain($eloquent));
    }

    public function findUserRoles(int $userId): array
    {
        $user = EloquentUser::findOrFail($userId);
        return $user->roles
        ->map(fn($role) => RolesAndPermissionMapper::toRoleDomain($role))
        ->toArray();

    }
    public function getUserWithStudentDetail(int $userId): User
    {
        $eloquent = EloquentUser::findOrFail($userId);
        $eloquent->load('studentDetail');
        return UserMapper::toDomain($eloquent);
    }

    public function getUserByStripeCustomer(string $customerId): User
    {
        $user = EloquentUser::where('stripe_customer_id', $customerId)->first();
        if (!$user) {
            throw new ModelNotFoundException('Usuario no encontrado');
        }
        return UserMapper::toDomain($user);
    }

    public function findUserByEmail(string $email): ?User
    {
        $user=EloquentUser::with('roles')->where('email',$email)->first();
        return $user ? UserMapper::toDomain($user): null;

    }

    public function getUserIdsByControlNumbers(array $controlNumbers): UserIdListDTO
    {
        $ids = EloquentUser::whereHas('studentDetail', fn($q) => $q->whereIn('n_control', $controlNumbers))
        ->where('status', UserStatus::ACTIVO)
        ->pluck('id')
        ->toArray();

        return MappersUserMapper::toUserIdListDTO($ids);
    }

    public function getControlNumbersBySearch(string $search, int $limit = 15): array
    {
        return EloquentUser::query()
            ->select(['id', 'name', 'last_name'])
            ->with(['studentDetail:id,user_id,n_control'])
            ->where('status', UserStatus::ACTIVO)
            ->whereHas('studentDetail', function ($q) use ($search) {
                $q->where('n_control', 'like', $search . '%');
            })
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                $nControl = $user->studentDetail?->n_control;
                $fullName = trim($user->name . ' ' . $user->last_name);

                return [
                    'id' => $user->id,
                    'n_control' => $nControl,
                    'name' => $fullName,
                    'text' => "{$nControl} - {$fullName}"
                ];
            })
            ->toArray();
    }

    public function getUsersPopulationSummary(bool $onlyThisYear): UsersFinancialSummary
    {
        $roles = DB::table('roles')
            ->whereIn('name', [
                UserRoles::STUDENT->value,
                UserRoles::APPLICANT->value
            ])
            ->pluck('id', 'name');

        $counts = DB::table('users')
            ->join('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', EloquentUser::class);
            })
            ->where('users.status', UserStatus::ACTIVO)
            ->when($onlyThisYear, function ($q) {
                $start = now()->startOfYear();
                $end = now()->endOfYear();
                $q->whereBetween('users.created_at', [$start, $end]);
            })
            ->whereIn('model_has_roles.role_id', $roles->values())
            ->selectRaw('model_has_roles.role_id, COUNT(DISTINCT users.id) as total')
            ->groupBy('model_has_roles.role_id')
            ->pluck('total', 'model_has_roles.role_id');

        return MappersUserMapper::toUsersFinancialSummary(
            totalStudents: $counts[$roles[UserRoles::STUDENT->value]] ?? 0,
            totalApplicants: $counts[$roles[UserRoles::APPLICANT->value]] ?? 0
        );
    }

    public function getUsersAdminSummary(bool $onlyThisYear): UsersAdminSummary
    {
        [$populationSummary, $recentActivity] = EloquentAdminUserDashboardQuery::getGlobalUserStats($onlyThisYear);

        $usersByRoleSummary = EloquentAdminUserDashboardQuery::getUsersByRoleSummary();

        [$academicSummary, $systemAlerts] = EloquentAdminUserDashboardQuery::getStudentAcademicAndAlerts($onlyThisYear);

        return new UsersAdminSummary(
            populationSummary: $populationSummary,
            usersByRoleSummary: $usersByRoleSummary,
            academicSummary: $academicSummary,
            systemAlerts: $systemAlerts,
            recentActivity: $recentActivity
        );
    }
    public function findBySearch(string $search): ?User
    {
        $user = EloquentUser::with('studentDetail')
            ->where(function ($q) use ($search) {
            $q->where('curp', 'like', "%$search%")
              ->orWhere('email', 'like', "%$search%")
              ->orWhereHas('studentDetail', function($q2) use ($search) {
                  $q2->where('n_control', 'like', "%$search%");
              });
        })
        ->first();
        return $user ? UserMapper::toDomain($user) : null;
    }

    public function findActiveStudents(?string $search, int $perPage, int $page): LengthAwarePaginator
    {
        $query = EloquentUser::query()
            ->select('id','name','last_name','email')
            ->where('status', UserStatus::ACTIVO)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', UserRoles::students());
            });

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('curp', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhereHas('studentDetail', function($q2) use ($search) {
                        $q2->where('n_control', 'like', "%$search%");
                    });
            });
        }
        $query->orderBy('users.name', 'asc')
            ->orderBy('users.last_name', 'asc');
        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function getRecipientsIds(PaymentConcept $concept, string $appliesTo): array
    {
        $usersQuery = EloquentUser::query()
            ->where('status', UserStatus::ACTIVO)
            ->select('id');

        $this->matchRecipients($usersQuery, $concept, $appliesTo);


        return $usersQuery->pluck('id')->toArray();
    }

    public function getRecipientsFromIds(array $ids): array
    {
        if(empty($ids))
        {
            return [];
        }
        $usersQuery = EloquentUser::query()
            ->whereIn('id', $ids)
            ->where('status', UserStatus::ACTIVO)
            ->select(['id', 'name', 'last_name', 'email']);

        $recipients = [];
        $page = 1;
        $pageSize = 100;

        do {
            $users = $usersQuery->forPage($page, $pageSize)
                ->orderBy('name')
                ->orderBy('last_name')
                ->get();

            foreach ($users as $user) {
                $recipients[] = MappersUserMapper::toRecipientDTO($user->toArray());
            }

            $page++;
        } while ($users->count() === $pageSize);

        return $recipients;
    }

    public function hasAnyRecipient(PaymentConcept $concept, string $appliesTo): bool
    {
        $usersQuery = EloquentUser::query()
            ->where('status', UserStatus::ACTIVO)
            ->select(DB::raw('1'));

        $this->matchRecipients($usersQuery, $concept, $appliesTo);

        return $usersQuery->exists();
    }

    public function getRecipients(PaymentConcept $concept, string $appliesTo): array
    {
        $usersQuery = EloquentUser::query()
            ->where('status', UserStatus::ACTIVO)
            ->select(['id', 'name', 'last_name', 'email']);


        $this->matchRecipients($usersQuery, $concept, $appliesTo);

        $recipients = [];
        $page = 1;
        $pageSize = 100;

        do {
            $users = $usersQuery->forPage($page, $pageSize)
                ->orderBy('name')
                ->orderBy('last_name')
                ->get();

            foreach ($users as $user) {
                $recipients[] = MappersUserMapper::toRecipientDTO($user->toArray());
            }

            $page++;
        } while ($users->count() === $pageSize);

        return $recipients;
    }

    private function matchRecipients(Builder &$usersQuery, PaymentConcept $concept, string $appliesTo): void
    {
        match($appliesTo) {
            PaymentConceptAppliesTo::CARRERA->value => $usersQuery->whereHas('studentDetail', function($q) use ($concept) {
                $q->whereIn('career_id', $concept->getCareerIds());
            }),
            PaymentConceptAppliesTo::SEMESTRE->value => $usersQuery->whereHas('studentDetail', function($q) use ($concept) {
                $q->whereIn('semestre', $concept->getSemesters());
            }),
            PaymentConceptAppliesTo::CARRERA_SEMESTRE->value => $usersQuery->whereHas('studentDetail', function($q) use ($concept){
                $q->whereIn('career_id', $concept->getCareerIds())
                    ->whereIn('semestre', $concept->getSemesters());
            }),
            PaymentConceptAppliesTo::ESTUDIANTES->value => $usersQuery->whereIn('id', $concept->getUserIds()),
            PaymentConceptAppliesTo::TODOS->value => $usersQuery->role(UserRoles::STUDENT->value),
            PaymentConceptAppliesTo::TAG->value => $usersQuery->where(function ($query) use ($concept) {
                $query->where(function ($subQuery) use ($concept) {
                    foreach ($concept->getApplicantTag() as $tag) {
                        match($tag) {
                            PaymentConceptApplicantType::NO_STUDENT_DETAILS =>
                            $subQuery->orWhere(function($q) {
                                $q->whereDoesntHave('studentDetail')
                                    ->role(UserRoles::STUDENT->value);
                            }),

                            PaymentConceptApplicantType::APPLICANT =>
                            $subQuery->orWhere(function($q) {
                                $q->role(UserRoles::APPLICANT->value);
                            }),
                            default => null,
                        };
                    }
                });
            }),
            default => null,
        };

        $exceptionIds = $concept->getExceptionUsersIds();
        if (!empty($exceptionIds)) {
            $usersQuery->whereNotIn('id', $exceptionIds);
        }

    }


    public function hasRole(int $userId, string $role): bool
    {
        $eloquentUser = EloquentUser::find($userId);
        return $eloquentUser ? $eloquentUser->hasRole($role) : false;
    }

    public function getStudentsWithPendingSummary(array $userIds): array
    {
        if (empty($userIds)) return [];

        $paidTotals = DB::table('payments')
            ->select(
                'payments.user_id',
                DB::raw('COALESCE(SUM(payments.amount_received), 0) AS total_paid'),
                DB::raw('COUNT(payments.id) AS total_paid_concepts')
            )
            ->whereIn('payments.status', PaymentStatus::paidStatuses())
            ->whereIn('payments.user_id', $userIds)
            ->groupBy('payments.user_id');

        $rows = $this->basePendingLeftJoinQuery($userIds)
            ->leftJoin('student_details', 'student_details.user_id', '=', 'users.id')
            ->leftJoin('careers', 'careers.id', '=', 'student_details.career_id')
            ->leftJoinSub(
                $paidTotals,
                'paid_totals',
                'paid_totals.user_id',
                '=',
                'users.id'
            )
            ->selectRaw("
                users.id AS user_id,
                CONCAT(users.name, ' ', users.last_name) AS full_name,
                student_details.n_control as n_control,
                student_details.semestre AS semestre,
                careers.career_name AS career,
                COUNT(CASE WHEN pending_concepts.is_expired = 0 THEN 1 END) AS total_count,
                COALESCE(SUM(CASE WHEN pending_concepts.is_expired = 0 THEN pending_concepts.pending_amount END), 0) AS total_amount,
                COALESCE(
                    SUM(CASE WHEN pending_concepts.is_expired = 1 THEN 1 END),
                    0
                ) AS expired_count,
                COALESCE(
                    SUM(
                        CASE
                            WHEN pending_concepts.is_expired = 1
                            THEN pending_concepts.pending_amount

                        END
                    ),
                    0
                ) AS expired_amount,
                COALESCE(paid_totals.total_paid, 0) AS total_paid,
                COALESCE(paid_totals.total_paid_concepts, 0) AS total_paid_concepts
            ")
            ->groupBy(
                'users.id',
                'users.name',
                'users.last_name',
                'student_details.n_control',
                'student_details.semestre',
                'careers.career_name',
                'paid_totals.total_paid',
                'paid_totals.total_paid_concepts'
            )
            ->orderBy('full_name', 'asc')
            ->get();

        return $rows->map(fn($r) => MappersUserMapper::toUserWithPendingSummaryResponse([
            'user_id'      => (int)$r->user_id,
            'name'         => $r->full_name,
            'n_control' => $r->n_control,
            'semestre'     => $r->semestre,
            'career'       => $r->career ?? null,
            'total_count'  => (int)$r->total_count,
            'total_amount' => $r->total_amount,
            'expired_count' => (int) $r->expired_count,
            'expired_amount' => $r->expired_amount,
            'total_paid_concepts' => (int) $r->total_paid_concepts,
            'total_paid'     => $r->total_paid,
        ]))->toArray();
    }

    public function findAllUsers(int $perPage, int $page, ?UserStatus $status = null): LengthAwarePaginator
    {
        return EloquentUser::whereDoesntHave('roles', function($query) {
            $query->where('name', UserRoles::ADMIN->value);
        })
            ->when($status, fn ($q) => $q->where('status', $status->value))
            ->select('id', 'name', 'last_name', 'curp' ,'email', 'status', 'created_at', 'mark_as_deleted_at')
            ->selectRaw(
                '(
                    SELECT COUNT(*)
                    FROM model_has_roles
                    WHERE model_has_roles.model_id = users.id
                    AND model_has_roles.model_type = ?
                ) as roles_count', [EloquentUser::class]
            )
            ->latest('users.created_at')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn($user) => MappersUserMapper::toUserListItemResponse($user));
    }

    public function getExtraUserData(int $userId): UserExtraDataResponse
    {
        $user = EloquentUser::with([
            'roles:id,name',
            'permissions:id,name',
        ])
            ->select('id', 'birthdate', 'phone_number', 'address', 'blood_type', 'registration_date')
            ->findOrFail($userId);

        $isStudent = $user->roles->contains('name', UserRoles::STUDENT->value);
        if ($isStudent) {
            $user->load([
                'studentDetail' => function($query) {
                    $query->select('id', 'user_id', 'career_id', 'n_control', 'semestre', 'group', 'workshop')
                        ->with('career:id,career_name');
                }
            ]);
        }

        return MappersUserMapper::toUserExtrDataResponse($user);
    }

    public function findAuthUser(): ?UserAuthResponse
    {
        /** @var \App\Models\User $user */
        $user=Auth::user();
        if (! $user) {
            return null;
        }
        return  MappersUserMapper::toUserAuthResponse($user);
    }

    public function findByIds(array $ids): Collection
    {
        if (empty($ids)) {
            return collect();
        }

        return EloquentUser::whereIn('id', $ids)
            ->cursor()
            ->map(fn($user) => UserMapper::toDomain($user))
            ->collect();

    }
    public function findModelEntity(int $userId): EloquentUser
    {
        return EloquentUser::findOrFail($userId);
    }

    public function getUsersByRoleCursor(string $role): \Generator
    {
        foreach (EloquentUser::role($role)
                     ->select('id', 'name', 'last_name', 'curp')
                     ->with('roles:id,name')
            ->orderBy('id')
                     ->cursor() as $user) {
            yield $user;
        }
    }

    public function getUsersByCurpCursor(array $curps): \Generator
    {
        foreach (EloquentUser::whereIn('curp', $curps)
                     ->select('id', 'name', 'last_name', 'curp')
                     ->with('roles:id,name')
                     ->orderBy('id')
                     ->cursor() as $user) {
            yield $user;
        }
    }
    public function userHasUnreadNotifications(int $userId): bool
    {
        return DB::table('notifications')
            ->where('notifiable_id', $userId)
            ->where('notifiable_type', EloquentUser::class)
            ->whereNull('read_at')
            ->exists();
    }
}
