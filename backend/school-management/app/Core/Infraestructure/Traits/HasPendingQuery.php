<?php

namespace App\Core\Infraestructure\Traits;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptTimeScope;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait HasPendingQuery
{
    public function basePendingQuery(array $userIds, PaymentConceptTimeScope $scope= PaymentConceptTimeScope::ONLY_ACTIVE)
    {
        $now = Carbon::now()->toDateString();

        $usersContext = DB::table('users')
            ->leftJoin('student_details', 'student_details.user_id', '=', 'users.id')
            ->leftJoin('model_has_roles', function($join) {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', '=', User::class);
            })
            ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereIn('users.id', $userIds)
            ->where('users.status', UserStatus::ACTIVO->value)
            ->select(
                'users.id as user_id',
                'users.created_at as user_created_at',
                'student_details.career_id',
                'student_details.semestre',
                DB::raw("
                    CASE
                        WHEN roles.name = 'applicant'
                            THEN '" . PaymentConceptApplicantType::APPLICANT->value . "'
                        WHEN student_details.id IS NULL
                            THEN '" . PaymentConceptApplicantType::NO_STUDENT_DETAILS->value . "'

                        ELSE NULL
                    END as applicant_type
                "),
                DB::raw("CASE WHEN roles.name = '" . UserRoles::STUDENT->value . "' THEN 1 ELSE 0 END as is_student")
            )
            ->distinct('users.id')
        ;

        $baseConcepts = DB::table('payment_concepts')
            ->whereDate('payment_concepts.start_date', '<=', $now)
            ->when(
                $scope === PaymentConceptTimeScope::ONLY_ACTIVE,
                fn($q) => $q->where('payment_concepts.status', PaymentConceptStatus::ACTIVO->value)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('payment_concepts.end_date')
                            ->orWhereDate('payment_concepts.end_date', '>=', $now);
                    }),
                fn($q) => $q->whereIn('payment_concepts.status', [
                    PaymentConceptStatus::ACTIVO->value,
                    PaymentConceptStatus::FINALIZADO->value
                ])
            );


        $pending = DB::query()
            ->fromSub($usersContext, 'u')
            ->joinSub($baseConcepts, 'payment_concepts', fn () => true)
            ->where(function($q) {
                $q->whereNull('payment_concepts.end_date')
                    ->orWhere('payment_concepts.end_date', '>=', DB::raw('DATE(u.user_created_at)'));
            })
            ->leftJoin('payments', function ($q) {
                $q->on('payments.payment_concept_id', '=', 'payment_concepts.id')
                    ->on('payments.user_id', '=', 'u.user_id');
            })

            ->leftJoin('concept_exceptions', function ($q) {
                $q->on('concept_exceptions.payment_concept_id', '=', 'payment_concepts.id')
                    ->on('concept_exceptions.user_id', '=', 'u.user_id');
            })

            ->where(function($q) {
                $q->whereNull('payments.id')
                    ->orWhere(function($q2) {
                        $q2->whereIn('payments.status', PaymentStatus::nonTerminalStatuses());
                        $q2->whereRaw('payments.id = (
                          SELECT MAX(p2.id)
                          FROM payments p2
                          WHERE p2.payment_concept_id = payments.payment_concept_id
                            AND p2.user_id = payments.user_id
                      )');
                    });
            })
            ->whereNull('concept_exceptions.id')

            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->where('payment_concepts.applies_to', PaymentConceptAppliesTo::TODOS->value)
                        ->where('u.is_student', 1);
                })

                ->orWhere(function ($sub) {
                    $sub->where('payment_concepts.applies_to', PaymentConceptAppliesTo::ESTUDIANTES->value)
                        ->whereExists(function ($exists) {
                            $exists->select(DB::raw(1))
                                ->from('payment_concept_user')
                                ->whereColumn('payment_concept_user.payment_concept_id', 'payment_concepts.id')
                                ->whereColumn('payment_concept_user.user_id', 'u.user_id');
                        });
                })

                ->orWhere(function ($sub) {
                    $sub->where('payment_concepts.applies_to', PaymentConceptAppliesTo::CARRERA->value)
                        ->whereExists(function ($exists) {
                            $exists->select(DB::raw(1))
                                ->from('career_payment_concept')
                                ->whereColumn('career_payment_concept.payment_concept_id', 'payment_concepts.id')
                                ->whereColumn('career_payment_concept.career_id', 'u.career_id');
                        });
                })

                ->orWhere(function ($sub) {
                    $sub->where('payment_concepts.applies_to', PaymentConceptAppliesTo::SEMESTRE->value)
                        ->whereExists(function ($exists) {
                            $exists->select(DB::raw(1))
                                ->from('payment_concept_semester')
                                ->whereColumn('payment_concept_semester.payment_concept_id', 'payment_concepts.id')
                                ->whereColumn('payment_concept_semester.semestre', 'u.semestre');
                        });
                })

                ->orWhere(function ($sub) {
                    $sub->where('payment_concepts.applies_to', PaymentConceptAppliesTo::CARRERA_SEMESTRE->value)
                        ->whereExists(function ($exists) {
                            $exists->select(DB::raw(1))
                                ->from('career_payment_concept')
                                ->whereColumn('career_payment_concept.payment_concept_id', 'payment_concepts.id')
                                ->whereColumn('career_payment_concept.career_id', 'u.career_id');
                        })
                        ->whereExists(function ($exists) {
                            $exists->select(DB::raw(1))
                                ->from('payment_concept_semester')
                                ->whereColumn('payment_concept_semester.payment_concept_id', 'payment_concepts.id')
                                ->whereColumn('payment_concept_semester.semestre', 'u.semestre');
                        });
                })

                ->orWhere(function ($sub) {
                    $sub->where('payment_concepts.applies_to', PaymentConceptAppliesTo::TAG->value)
                        ->whereExists(function ($exists) {
                            $exists->select(DB::raw(1))
                                ->from('payment_concept_applicant_tags')
                                ->whereColumn('payment_concept_applicant_tags.payment_concept_id', 'payment_concepts.id')
                                ->whereColumn('payment_concept_applicant_tags.tag', 'u.applicant_type');
                        });
                });
            })

            ->select(
                'payment_concepts.*',
                DB::raw('COALESCE(payment_concepts.amount - COALESCE(payments.amount_received, 0), payment_concepts.amount) as pending_amount'),
                'u.user_id as target_user_id',
                DB::raw("
                    CASE
                        WHEN payment_concepts.end_date IS NOT NULL
                             AND payment_concepts.end_date < CURRENT_DATE
                        THEN 1
                        ELSE 0
                    END as is_expired
                ")
            );

        return $pending;
    }

    public function basePendingLeftJoinQuery(array $userIds, PaymentConceptTimeScope $scope = PaymentConceptTimeScope::INCLUDE_EXPIRED)
    {
        return DB::table('users')
            ->whereIn('users.id', $userIds)
            ->leftJoinSub(
                $this->basePendingQuery($userIds, $scope),
                'pending_concepts',
                'pending_concepts.target_user_id',
                '=',
                'users.id'
            );
    }

}


