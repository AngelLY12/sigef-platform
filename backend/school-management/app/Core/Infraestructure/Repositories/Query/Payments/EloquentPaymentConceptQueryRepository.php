<?php

namespace App\Core\Infraestructure\Repositories\Query\Payments;

use App\Core\Application\DTO\Response\PaymentConcept\ConceptRelationsToDisplay;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptToDisplay;
use App\Core\Application\DTO\Response\PaymentConcept\PendingSummaryResponse;
use App\Core\Application\Mappers\PaymentConceptMapper as MappersPaymentConceptMapper;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Utils\Helpers\DateHelper;
use App\Core\Domain\Utils\Helpers\Money;
use App\Core\Infraestructure\Mappers\PaymentConceptMapper;
use App\Core\Infraestructure\Traits\HasPendingQuery;
use App\Models\PaymentConcept as EloquentPaymentConcept;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentPaymentConceptQueryRepository implements PaymentConceptQueryRepInterface
{
    use HasPendingQuery;

    public function findById(int $id): ?PaymentConcept
    {
        return optional(EloquentPaymentConcept::with(['careers', 'users', 'paymentConceptSemesters', 'exceptions', 'applicantTypes'])->find($id), fn($pc) => PaymentConceptMapper::toDomain($pc));
    }

    public function findByIdToDisplay(int $id): ?ConceptToDisplay
    {
        $concept = EloquentPaymentConcept::select(
            [
                'id',
                'concept_name',
                'description',
                'amount',
                'applies_to',
                'status',
                'start_date',
                'end_date',
                'created_at',
                'updated_at',
                'mark_as_deleted_at'
            ])->find($id);
        if (! $concept) {
            return null;
        }
        return MappersPaymentConceptMapper::toDisplay($concept);
    }

    public function findRelationsByIdToDisplay(int $id): ?ConceptRelationsToDisplay
    {
        $concept = EloquentPaymentConcept::query()
            ->select(['id', 'concept_name', 'applies_to'])
            ->find($id);
        if (! $concept) {
            return null;
        }
        $this->loadRelationsBasedOnAppliesTo($concept);
        return MappersPaymentConceptMapper::toRelationsDisplay($concept);
    }

    public function getPendingPaymentConcepts(User $user, bool $onlyThisYear): PendingSummaryResponse {
        $query = $this->basePendingPaymentConcept($user);

        if ($onlyThisYear) {
            $query->whereYear('payment_concepts.created_at', now()->year);
        }

        $result=$query->selectRaw('COALESCE(SUM(payment_concepts.amount - COALESCE(p.amount_received,0)), 0) as total_amount,
                 COUNT(DISTINCT payment_concepts.id) as total_count')
            ->first();


        return MappersPaymentConceptMapper::toPendingPaymentSummary($this->formattSummaryResponse($result));
    }

    public function getPendingPaymentConceptsWithDetails(User $user): array
    {
        $rows = $this->basePendingPaymentConcept($user)
            ->select(
                [
                    'payment_concepts.id',
                    'payment_concepts.concept_name',
                    'payment_concepts.description',
                    'payment_concepts.start_date',
                    'payment_concepts.end_date',
                    'payment_concepts.status',
                    DB::raw('COALESCE(payment_concepts.amount - COALESCE(p.amount_received,0), payment_concepts.amount) as amount')

                ])
            ->orderBy('payment_concepts.created_at', 'desc')
            ->get();

        return $rows->map(fn($pc) => MappersPaymentConceptMapper::toPendingPaymentConceptResponse($pc->toArray()))->toArray();
    }

    public function getOverduePaymentsSummary(User $user, bool $onlyThisYear): PendingSummaryResponse
    {
        $query= $this->baseOverduePaymentConcept($user);

        if ($onlyThisYear) {
            $query->whereYear('payment_concepts.created_at', now()->year);
        }

        $result=$query->selectRaw('COALESCE(SUM(payment_concepts.amount - COALESCE(p.amount_received,0)), 0) as total_amount,
                     COUNT(DISTINCT payment_concepts.id) as total_count')
            ->first();

        return MappersPaymentConceptMapper::toPendingPaymentSummary($this->formattSummaryResponse($result));
    }

    public function getOverduePayments(User $user): array
    {
        $rows = $this->baseOverduePaymentConcept($user)
            ->select(
                [
                    'payment_concepts.id',
                    'payment_concepts.concept_name',
                    'payment_concepts.description',
                    'payment_concepts.start_date',
                    'payment_concepts.end_date',
                    'payment_concepts.status',
                     DB::raw('
                        COALESCE(
                            payment_concepts.amount - COALESCE(p.amount_received, 0),
                            payment_concepts.amount
                        ) as amount
                    ')
                ])
            ->latest('payment_concepts.created_at')
            ->get();

        return $rows->map(fn($pc) => MappersPaymentConceptMapper::toPendingPaymentConceptResponse($pc->toArray()))->toArray();
    }

    public function findAllConcepts(string $status, int $perPage, int $page): LengthAwarePaginator
    {
        $query = EloquentPaymentConcept::query()
            ->select(['id','concept_name','status', 'description' ,'applies_to','end_date','amount', 'mark_as_deleted_at'])
            ->latest('created_at');

        if ($status !== 'todos') {
            $query->where('status', $status);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $paginator->getCollection()->transform(function ($concept) {
            return [
                'id' => $concept->id,
                'concept_name' => $concept->concept_name,
                'amount' => $concept->amount,
                'description' => $concept->description,
                'status' => $concept->status->value,
                'applies_to' => $concept->applies_to->value,
                'expiration_human' => $concept->end_date
                    ? DateHelper::expirationToHuman($concept->end_date, $concept->status->value ?? null)
                    : null,
                'days_until_deletion' => $concept->mark_as_deleted_at ? DateHelper::daysUntilDeletion($concept->mark_as_deleted_at) : null,
                'has_expiration' => !is_null($concept->end_date),
                'is_deleted' => !is_null($concept->mark_as_deleted_at),
            ];
        });

        return $paginator;
    }

    public function getAllPendingPaymentAmount(bool $onlyThisYear): PendingSummaryResponse
    {
        $userStatusActivo = UserStatus::ACTIVO->value;
        $userModelClass = \App\Models\User::class;
        $studentRole = UserRoles::STUDENT->value;

        $terminalStatus1 = PaymentStatus::SUCCEEDED->value;
        $terminalStatus2 = PaymentStatus::OVERPAID->value;
        $terminalStatus3 = PaymentStatus::PAID->value;

        $appliesTodos = PaymentConceptAppliesTo::TODOS->value;
        $appliesEstudiantes = PaymentConceptAppliesTo::ESTUDIANTES->value;
        $appliesCarrera = PaymentConceptAppliesTo::CARRERA->value;
        $appliesSemestre = PaymentConceptAppliesTo::SEMESTRE->value;
        $appliesTag = PaymentConceptAppliesTo::TAG->value;

        $tagApplicant = PaymentConceptApplicantType::APPLICANT->value;
        $tagNoStudentDetails = PaymentConceptApplicantType::NO_STUDENT_DETAILS->value;

        $query = DB::table('payment_concepts as pc')
            ->selectRaw("
            COALESCE(SUM(
                pc.amount * (
                    SELECT COUNT(DISTINCT u.id)
                    FROM users u
                    WHERE u.status = ?
                    AND EXISTS (
                        SELECT 1 FROM model_has_roles mhr
                        JOIN roles r ON mhr.role_id = r.id
                        WHERE mhr.model_id = u.id
                        AND mhr.model_type = ?
                        AND r.name = ?
                    )
                    AND NOT EXISTS (
                        SELECT 1 FROM concept_exceptions pce
                        WHERE pce.payment_concept_id = pc.id
                        AND pce.user_id = u.id
                    )
                    AND NOT EXISTS (
                        SELECT 1 FROM payments p
                        WHERE p.payment_concept_id = pc.id
                        AND p.user_id = u.id
                        AND p.status IN (?, ?, ?)
                        AND p.id = (
                            SELECT MAX(p2.id)
                            FROM payments p2
                            WHERE p2.payment_concept_id = p.payment_concept_id
                            AND p2.user_id = p.user_id
                        )
                    )
                    AND (
                        (pc.applies_to = ?)
                        OR (pc.applies_to = ? AND EXISTS (
                            SELECT 1 FROM payment_concept_user pcu
                            WHERE pcu.payment_concept_id = pc.id
                            AND pcu.user_id = u.id
                        ))
                        OR (pc.applies_to = ? AND EXISTS (
                            SELECT 1 FROM student_details sd
                            WHERE sd.user_id = u.id
                            AND EXISTS (
                                SELECT 1 FROM career_payment_concept pcc
                                WHERE pcc.career_id = sd.career_id
                                AND pcc.payment_concept_id = pc.id
                            )
                        ))
                        OR (pc.applies_to = ? AND EXISTS (
                            SELECT 1 FROM student_details sd
                            WHERE sd.user_id = u.id
                            AND EXISTS (
                                SELECT 1 FROM payment_concept_semester pcs
                                WHERE pcs.semestre = sd.semestre
                                AND pcs.payment_concept_id = pc.id
                            )
                        ))
                        OR (pc.applies_to = ? AND EXISTS (
                            SELECT 1 FROM payment_concept_applicant_tags at
                            WHERE at.payment_concept_id = pc.id
                            AND (
                                (at.tag = ? AND EXISTS (
                                    SELECT 1 FROM model_has_roles mhr2
                                    JOIN roles r2 ON mhr2.role_id = r2.id
                                    WHERE mhr2.model_id = u.id
                                    AND mhr2.model_type = ?
                                    AND r2.name = ?
                                ))
                                OR (at.tag = ? AND NOT EXISTS (
                                    SELECT 1 FROM student_details sd
                                    WHERE sd.user_id = u.id
                                ))
                            )
                        ))
                    )
                )
            ), 0) as total_bruto,
            COALESCE(SUM(ntp.amount_received), 0) as total_pagado,
            COUNT(DISTINCT pc.id) as total_count
        ", [
                $userStatusActivo,
                $userModelClass,
                $studentRole,

                $terminalStatus1,
                $terminalStatus2,
                $terminalStatus3,

                $appliesTodos,
                $appliesEstudiantes,
                $appliesCarrera,
                $appliesSemestre,
                $appliesTag,

                $tagApplicant,
                $userModelClass,
                $tagApplicant,
                $tagNoStudentDetails,
            ]);

        $subquery = DB::table('payments')
            ->selectRaw('payment_concept_id, SUM(amount_received) as amount_received')
            ->whereNotIn('status', [$terminalStatus1, $terminalStatus2, $terminalStatus3])
            ->groupBy('payment_concept_id');

        $query->leftJoinSub($subquery, 'ntp', function($join) {
            $join->on('ntp.payment_concept_id', '=', 'pc.id');
        });

        $query->where('pc.status', PaymentConceptStatus::ACTIVO->value)
            ->whereDate('pc.start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('pc.end_date')
                    ->orWhereDate('pc.end_date', '>=', now());
            });

        if ($onlyThisYear) {
            $query->whereYear('pc.created_at', now()->year);
        }

        $result = $query->first();

        $bruto = Money::from($result->total_bruto ?? '0');
        $pagado = Money::from($result->total_pagado ?? '0');
        $totalAmount = $bruto->sub($pagado);

        $totalAmount = $totalAmount->isNegative()
            ? Money::from('0')
            : $totalAmount;

        return MappersPaymentConceptMapper::toPendingPaymentSummary([
            'total_amount' => $totalAmount->finalize(),
            'total_count' => $result->total_count ?? 0
        ]);
    }

    public function getConceptsToDashboard(bool $onlyThisYear, int $perPage, int $page): LengthAwarePaginator
    {
        $query = EloquentPaymentConcept::select(['id', 'concept_name', 'status', 'start_date', 'end_date', 'amount', 'applies_to'])
                ->latest('created_at');

            if ($onlyThisYear) {
                $query->whereYear('created_at', now()->year);
            }
         $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $paginator->getCollection()->transform(fn($pc) => MappersPaymentConceptMapper::toConceptsToDashboardResponse($pc));

        return $paginator;
    }

    public function getPendingWithDetailsForStudents(array $userIds): array
    {
        if (empty($userIds)) return [];

        $rows = DB::query()
            ->fromSub($this->basePendingQuery($userIds), 'pending_concepts')
            ->join('users', 'users.id', '=', 'pending_concepts.target_user_id')
            ->leftJoin('student_details', 'student_details.user_id', '=', 'users.id')
            ->select(
                'users.id as user_id',
                DB::raw("CONCAT(users.name, ' ', users.last_name) as user_name"),
                'student_details.n_control as n_control',
                'pending_concepts.concept_name',
                'pending_concepts.pending_amount',
                'pending_concepts.created_at'
            )
            ->latest('pending_concepts.created_at')
            ->get();

        return $rows->map(fn($r) => MappersPaymentConceptMapper::toConceptNameAndAmoutResonse([
            'user_id' => $r->user_id,
            'user_name'    => $r->user_name,
            'n_control'    => $r->n_control,
            'concept_name' => $r->concept_name,
            'amount'       => $r->pending_amount,
        ]))->toArray();
    }

    private function basePaymentConcept(?User $user = null, $onlyActive=true, ?PaymentConceptStatus $status=null): Builder
    {

        $query = EloquentPaymentConcept::query();
        $now = now();
        if ($onlyActive) {
            $query->whereDate('payment_concepts.start_date', '<=', $now)
                ->where(fn($q) => $q->whereNull('payment_concepts.end_date')->orWhereDate('payment_concepts.end_date', '>=', $now));
        }

        if ($status) {
            $query->where('payment_concepts.status', $status);
        }

        if ($user) {
            $userId = $user->id;
            $careerId = $user->studentDetail?->career_id;
            $semester = $user->studentDetail?->semestre;
            $isApplicant = $user->isApplicant();
            $isNewStudent = $user->isNewStudent();
            $userCreatedAt = $user->created_at;

            $query->where(function($q) use ($userCreatedAt) {
                $q->whereNull('payment_concepts.end_date')
                ->orWhere('payment_concepts.end_date', '>=', $userCreatedAt);
            });

            $query->whereNotExists(function ($sub) use ($userId) {
                $sub->select(DB::raw(1))
                    ->from('payments')
                    ->whereColumn('payments.payment_concept_id', 'payment_concepts.id')
                    ->where('payments.user_id', $userId)
                    ->whereIn('payments.status', PaymentStatus::paidStatuses())
                    ->whereRaw('payments.id = (
                        SELECT MAX(p2.id)
                        FROM payments p2
                        WHERE p2.payment_concept_id = payments.payment_concept_id
                          AND p2.user_id = payments.user_id
                    )');
            });
            $query->whereDoesntHave('exceptions', fn($q) =>
                $q->where('user_id', $userId)
            );

            $query->where(function ($q) use ($userId, $careerId, $semester, $isApplicant, $isNewStudent) {
                $q->where(function($q) use ($userId) {
                    $q->where('applies_to', PaymentConceptAppliesTo::TODOS->value)
                        ->whereExists(function ($sub) use ($userId) {
                            $sub->select(DB::raw(1))
                                ->from('users as u')
                                ->join('model_has_roles as mhr', 'u.id', '=', 'mhr.model_id')
                                ->join('roles as r', 'mhr.role_id', '=', 'r.id')
                                ->where('u.id', $userId)
                                ->where('mhr.model_type', \App\Models\User::class)
                                ->where('r.name', UserRoles::STUDENT->value);
                        });
                });

                $q->orWhere(function($q) use ($userId) {
                    $q->where('applies_to', PaymentConceptAppliesTo::ESTUDIANTES->value)
                        ->whereHas('users', function ($q) use ($userId) {
                            $q->where('users.id', $userId);
                        });
                });

                if ($careerId) {
                    $q->orWhere(function($q) use ($careerId) {
                        $q->where('applies_to', PaymentConceptAppliesTo::CARRERA->value)
                            ->whereHas('careers', function ($q) use ($careerId) {
                                $q->where('careers.id', $careerId);
                            });
                    });
                }

                if ($semester) {
                    $q->orWhere(function($q) use ($semester) {
                        $q->where('applies_to', PaymentConceptAppliesTo::SEMESTRE->value)
                            ->whereHas('paymentConceptSemesters', function ($q) use ($semester) {
                                $q->where('semestre', $semester);
                            });
                    });
                }

                if ($isApplicant) {
                    $q->orWhere(function($q) {
                        $q->where('applies_to', PaymentConceptAppliesTo::TAG->value)
                            ->whereHas('applicantTypes', function ($q) {
                                $q->where('tag', PaymentConceptApplicantType::APPLICANT->value);
                            });
                    });
                }

                if ($isNewStudent) {
                    $q->orWhere(function($q) {
                        $q->where('applies_to', PaymentConceptAppliesTo::TAG->value)
                            ->whereHas('applicantTypes', function ($q) {
                                $q->where('tag', PaymentConceptApplicantType::NO_STUDENT_DETAILS->value);
                            });
                    });
                }
            });
        }

        return $query;
    }

    private function baseOverduePaymentConcept(User $user): Builder
    {
        return $this->basePaymentConcept($user, onlyActive: false, status: PaymentConceptStatus::FINALIZADO)
            ->leftJoin('payments as p', function ($join) use ($user) {
                $join->on('p.payment_concept_id', '=', 'payment_concepts.id')
                    ->where('p.user_id', $user->id)
                    ->whereNotIn('p.status', PaymentStatus::paidStatuses());
            });
    }

    private function basePendingPaymentConcept(User $user): Builder
    {
        return $this->basePaymentConcept($user, true, PaymentConceptStatus::ACTIVO )
            ->leftJoin('payments as p', function($join) use ($user) {
                $join->on('p.payment_concept_id', '=', 'payment_concepts.id')
                    ->where('p.user_id', $user->id)
                    ->whereNotIn('p.status', PaymentStatus::paidStatuses());
            });
    }

    private function formattSummaryResponse(?object $result): array
    {
        if ($result) {

            $array = $result->toArray();
            $totalAmount = $array['total_amount'] ?? '0.00';
            $totalCount = $array['total_count'] ?? 0;

            return  [
                'total_amount' => $totalAmount,
                'total_count' => $totalCount
            ];
        } else {
             return [
                'total_amount' => '0.00',
                'total_count' => 0
            ];
        }
    }

    private function loadRelationsBasedOnAppliesTo(EloquentPaymentConcept $concept): void
    {
        $relationMap = [
            PaymentConceptAppliesTo::ESTUDIANTES->value => ['users'],
            PaymentConceptAppliesTo::TAG->value => ['applicantTypes'],
            PaymentConceptAppliesTo::CARRERA->value => ['careers'],
            PaymentConceptAppliesTo::SEMESTRE->value => ['paymentConceptSemesters'],
            PaymentConceptAppliesTo::CARRERA_SEMESTRE->value => ['careers', 'paymentConceptSemesters'],
        ];

        $neededRelations = $relationMap[$concept->applies_to->value] ?? [];

        foreach ($neededRelations as $relation) {
            switch($relation) {
                case 'users':
                    $concept->loadMissing(['users.studentDetail:id,user_id,n_control']);
                    break;
                case 'careers':
                    $concept->loadMissing(['careers:id,career_name']);
                    break;
                case 'paymentConceptSemesters':
                    $concept->loadMissing(['paymentConceptSemesters:id,payment_concept_id,semestre']);
                    break;
                case 'applicantTypes':
                    $concept->loadMissing(['applicantTypes:id,tag']);
                    break;
            }
        }
        if (method_exists($concept, 'exceptions')) {
            $concept->loadMissing(['exceptions.studentDetail:id,user_id,n_control']);
        }
    }

}
