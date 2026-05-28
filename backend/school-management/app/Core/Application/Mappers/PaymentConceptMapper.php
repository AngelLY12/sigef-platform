<?php

namespace App\Core\Application\Mappers;

use App\Core\Application\DTO\Request\PaymentConcept\CreatePaymentConceptDTO;
use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptDTO;
use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptRelationsDTO;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptChangeStatusResponse;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptNameAndAmountResponse;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptRelationsToDisplay;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptsToDashboardResponse;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptToDisplay;
use App\Core\Application\DTO\Response\PaymentConcept\CreatePaymentConceptResponse;
use App\Core\Application\DTO\Response\PaymentConcept\PendingPaymentConceptsResponse;
use App\Core\Application\DTO\Response\PaymentConcept\PendingSummaryResponse;
use App\Core\Application\DTO\Response\PaymentConcept\UpdatePaymentConceptRelationsResponse;
use App\Core\Application\DTO\Response\PaymentConcept\UpdatePaymentConceptResponse;
use App\Core\Domain\Entities\PaymentConcept as EntitiesPaymentConcept;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Utils\Helpers\DateHelper;
use App\Core\Domain\Utils\Helpers\Money;
use App\Models\PaymentConcept;
use App\Models\PaymentConceptApplicantTag;
use Carbon\Carbon;

class PaymentConceptMapper{


    public static function toDomain(CreatePaymentConceptDTO $dto): EntitiesPaymentConcept
    {
        return new EntitiesPaymentConcept(
            concept_name: $dto->concept_name,
            status: $dto->status,
            start_date: $dto->start_date,
            amount: $dto->amount,
            applies_to: $dto->appliesTo,
            id: null,
            description: $dto->description,
            end_date: $dto->end_date
        );
    }

    public static function toDisplay(PaymentConcept $concept): ConceptToDisplay
    {
        $endDate = $concept->end_date;
        $deletedAt= $concept->mark_as_deleted_at;
        return new ConceptToDisplay(
            id: $concept->id,
            concept_name: $concept->concept_name,
            status: $concept->status->value,
            start_date: $concept->start_date->toDateString(),
            amount: $concept->amount,
            applies_to: $concept->applies_to->value,
            created_at_human: $concept->created_at->diffForHumans(),
            updated_at_human: $concept->updated_at->diffForHumans(),
            expiration_info: DateHelper::expirationInfo($endDate, $concept->status->value),
            expiration_human: DateHelper::expirationToHuman($endDate, $concept->status->value),
            deleted_at: $deletedAt?->toDateString(),
            deleted_at_human: $deletedAt?->diffForHumans(),
            days_until_deletion: DateHelper::daysUntilDeletion($deletedAt),
            description: $concept->description ?? null,
            end_date: $endDate?->toDateString(),
        );
    }

    public static function toRelationsDisplay(PaymentConcept $concept): ConceptRelationsToDisplay
    {
        return new ConceptRelationsToDisplay(
            id: $concept->id,
            concept_name: $concept->concept_name,
            applies_to: $concept->applies_to->value,
            users: $concept->relationLoaded('users')
                ? $concept->users
                    ->map(fn ($u) => $u->studentDetail?->n_control)
                    ->filter()
                    ->values()
                    ->toArray()
                : [],
            careers: $concept->relationLoaded('careers')
                ? $concept->careers->pluck('id')->toArray()
                : [],
            semesters: $concept->relationLoaded('paymentConceptSemesters')
                ? $concept->paymentConceptSemesters->pluck('semestre')->toArray()
                : [],
            exceptionUsers: $concept->relationLoaded('exceptions')
                ? $concept->exceptions
                    ->map(fn ($u) => $u->studentDetail?->n_control)
                    ->filter()
                    ->values()
                    ->toArray()
                : [],
            applicantTags: $concept->relationLoaded('applicantTypes')
                ? $concept->applicantTypes->pluck('tag')->toArray()
                : [],
        );
    }

   public static function toCreateConceptDTO(array $data): CreatePaymentConceptDTO
    {
        $statusEnum = isset($data['status'])
            ? PaymentConceptStatus::from(strtolower($data['status']))
            : PaymentConceptStatus::ACTIVO;

        $appliesToEnum = isset($data['applies_to'])
            ? PaymentConceptAppliesTo::from(strtolower($data['applies_to']))
            : PaymentConceptAppliesTo::TODOS;



        return new CreatePaymentConceptDTO(
            concept_name: $data['concept_name'],
            amount: Money::from((string) $data['amount'])->finalize(),
            status: $statusEnum,
            appliesTo: $appliesToEnum,
            description: $data['description'] ?? null,
            start_date: isset($data['start_date']) ? new Carbon($data['start_date']) : null,
            end_date: isset($data['end_date']) ? new Carbon($data['end_date']) : null,
            semesters: $data['semestres'] ?? [],
            careers: $data['careers'] ?? [],
            students: $data['students'] ?? [],
            exceptionStudents: $data['exceptionStudents'] ?? [],
            applicantTags: $data['applicantTags'] ?? [],
        );
    }

    public static function toUpdateConceptDTO(array $data): UpdatePaymentConceptDTO
    {
        $fieldsToUpdate = [];
        $allowedFields = ['concept_name', 'description', 'start_date', 'end_date', 'amount'];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                if (in_array($field, ['start_date', 'end_date']) && $data[$field] !== null) {
                    $fieldsToUpdate[$field] = new \Carbon\Carbon($data[$field]);
                } else {
                    $fieldsToUpdate[$field] = $data[$field];
                }
            }
        }

        return new UpdatePaymentConceptDTO(
            id: (int) $data['id'],
            concept_name: $fieldsToUpdate['concept_name'] ?? null,
            description: $fieldsToUpdate['description'] ?? null,
            start_date: $fieldsToUpdate['start_date'] ?? null,
            end_date: $fieldsToUpdate['end_date'] ?? null,
            amount:isset($fieldsToUpdate['amount'])
                ? Money::from((string)$fieldsToUpdate['amount'])->finalize()
                : null
        );
    }

    public static function toUpdateConceptRelationsDTO(array $data): UpdatePaymentConceptRelationsDTO
    {
        $appliesToEnum = isset($data['applies_to'])
            ? PaymentConceptAppliesTo::from(strtolower($data['applies_to']))
            : null;
        $applicantTagsEnum = [];
        if (isset($data['applicantTags']) && is_array($data['applicantTags'])) {
            foreach ($data['applicantTags'] as $tag) {
                $applicantTagsEnum[] = PaymentConceptApplicantType::from(strtolower($tag));
            }
        }
        return new UpdatePaymentConceptRelationsDTO(
            id: (int) $data['id'],
            semesters: $data['semestres'] ?? null,
            careers: $data['careers'] ?? null,
            students: $data['students'] ?? null,
            appliesTo: $appliesToEnum,
            replaceRelations: $data['replaceRelations'] ?? false,
            exceptionStudents: $data['exceptionStudents'] ?? null,
            replaceExceptions: $data['replaceExceptions'] ?? false,
            removeAllExceptions: $data['removeAllExceptions'] ?? false,
            applicantTags:$applicantTagsEnum,
        );
    }


    public static function toPendingPaymentConceptResponse(array $pc): PendingPaymentConceptsResponse {
        $startDate = isset($pc['start_date']) ? Carbon::parse($pc['start_date']) : null;
        $endDate = isset($pc['end_date']) ? Carbon::parse($pc['end_date']) : null;
        $status = $pc['status'] ?? null;
        if ($status instanceof PaymentConceptStatus) {
            $status = $status->value;
        }
        return new PendingPaymentConceptsResponse(
            id: $pc['id'] ?? null,
            concept_name: $pc['concept_name'] ?? null,
            description: $pc['description'] ?? null,
            amount: $pc['amount'] ?? null,
            start_date: $startDate?->format('Y-m-d H:i:s'),
            end_date: $endDate?->format('Y-m-d H:i:s'),
            expiration_human: DateHelper::expirationToHuman($endDate, $status),
            expiration_info: DateHelper::expirationInfo($endDate, $status),
        );
    }

    public static function toConceptsToDashboardResponse(PaymentConcept $pc): ConceptsToDashboardResponse {
        $endDate = $pc->end_date;
        return new ConceptsToDashboardResponse(
            id: $pc->id ?? null,
            concept_name: $pc->concept_name ?? null,
            status: $pc->status->value ?? null,
            amount: $pc->amount ?? null,
            applies_to:$pc->applies_to->value ?? null,
            start_date: $pc->start_date ? $pc->start_date->format('Y-m-d H:i:s') : null,
            end_date: $endDate?->format('Y-m-d H:i:s'),
            expiration_human: DateHelper::expirationToHuman($endDate, $pc->status->value ?? null),
        );

    }
    public static function toPendingPaymentSummary(array $data):PendingSummaryResponse
    {
        return new PendingSummaryResponse(
            totalAmount:$data['total_amount'] ?? null,
            totalCount:$data['total_count'] ?? null
        );

    }
    public static function toConceptNameAndAmoutResonse(array $data): ConceptNameAndAmountResponse
    {
        return new ConceptNameAndAmountResponse(
            userId: (int) $data['user_id'],
            user_name: $data['user_name'] ?? null,
            n_control: $data['n_control'] ?? null,
            concept_name: $data['concept_name'] ?? null,
            amount:$data['amount'] ?? null
        );
    }

    public static function toCreatePaymentConceptResponse(\App\Core\Domain\Entities\PaymentConcept $paymentConcept, int $affectedCount): CreatePaymentConceptResponse
    {
        return new CreatePaymentConceptResponse(
            id: $paymentConcept->id,
            conceptName: $paymentConcept->concept_name,
            status: $paymentConcept->status->value,
            appliesTo: $paymentConcept->applies_to->value,
            amount: $paymentConcept->amount,
            startDate: $paymentConcept->start_date->format('Y-m-d'),
            endDate: $paymentConcept->end_date?->format('Y-m-d'),
            affectedStudentsCount: $affectedCount,
            message: sprintf(
                'Concepto creado exitosamente. Afecta a %d estudiante(s)',
                $affectedCount
            ),
            createdAt: now()->format('Y-m-d H:i:s'),
            metadata: [
                'exception_count' => count($paymentConcept->getExceptionUsersIds()),
                'career_count' => count($paymentConcept->getCareerIds()),
                'semester_count' => count($paymentConcept->getSemesters()),
            ],
            description: $paymentConcept->description ?? null,
        );
    }

    public static function toUpdatePaymentConceptResponse(EntitiesPaymentConcept $newPaymentConcept, array $data): UpdatePaymentConceptResponse
    {
        return new UpdatePaymentConceptResponse(
            id: $newPaymentConcept->id,
            conceptName: $newPaymentConcept->concept_name,
            status: $newPaymentConcept->status->value,
            appliesTo: $newPaymentConcept->applies_to->value,
            description: $newPaymentConcept->description ?? null,
            amount: $newPaymentConcept->amount,
            startDate: $newPaymentConcept->start_date->format('Y-m-d'),
            endDate: $newPaymentConcept->end_date?->format('Y-m-d'),
            message: $data['message'] ?? null,
            updatedAt: now()->format('Y-m-d H:i:s'),
            changes: $data['changes'] ?? [],
        );
    }
    public static function toUpdatePaymentConceptRelationsResponse(EntitiesPaymentConcept $newPaymentConcept, array $data): UpdatePaymentConceptRelationsResponse
    {
        return new UpdatePaymentConceptRelationsResponse(
            status: $newPaymentConcept->status->value,
            metadata: [
                'concept_name' => $newPaymentConcept->concept_name,
                'applies_to' => $newPaymentConcept->applies_to->value,
                'students_count' => count($newPaymentConcept->getUserIds()),
                'exception_count' => count($newPaymentConcept->getExceptionUsersIds()),
                'career_count' => count($newPaymentConcept->getCareerIds()),
                'semester_count' => count($newPaymentConcept->getSemesters()),
                'tags' => [$newPaymentConcept->getApplicantTag()]
            ],
            message: $data['message'] ?? null,
            updatedAt: now()->format('Y-m-d H:i:s'),
            changes: $data['changes'] ?? [],
            affectedSummary: $data['affectedSummary'] ?? []
        );
    }

    public static function toConceptChangeStatusResponse(EntitiesPaymentConcept $paymentConcept, array $data): ConceptChangeStatusResponse
    {
        return new ConceptChangeStatusResponse(
            conceptData: [
                'id' => $paymentConcept->id,
                'concept_name' => $paymentConcept->concept_name,
                'status' => $paymentConcept->status->value,
                'amount' => $paymentConcept->amount,
                'start_date' => $paymentConcept->start_date->format('Y-m-d'),
                'end_date' => $paymentConcept->end_date?->format('Y-m-d'),
                'applies_to' => $paymentConcept->applies_to->value,
            ],
            updatedAt: now()->format('Y-m-d H:i:s'),
            changes: $data['changes'] ?? [],
            message: $data['message'] ?? null,
        );
    }
}
