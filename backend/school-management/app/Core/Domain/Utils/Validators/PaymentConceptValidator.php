<?php

namespace App\Core\Domain\Utils\Validators;
use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptDTO;
use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptRelationsDTO;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\User\UserRoles;
use App\Exceptions\Conflict\RemoveExceptionsAndExceptionStudentsOverlapException;
use App\Exceptions\Conflict\UserExplicitlyExcludedException;
use App\Exceptions\Validation\RequiredForAppliesToException;
use App\Exceptions\Validation\ValidationException;
use Carbon\Carbon;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Exceptions\Conflict\ConceptAlreadyActiveException;
use App\Exceptions\Conflict\ConceptAlreadyDeletedException;
use App\Exceptions\Conflict\ConceptAlreadyDisabledException;
use App\Exceptions\Conflict\ConceptAlreadyFinalizedException;
use App\Exceptions\Conflict\ConceptCannotBeDisabledException;
use App\Exceptions\Conflict\ConceptCannotBeFinalizedException;
use App\Exceptions\Conflict\ConceptCannotBeUpdatedException;
use App\Exceptions\Conflict\ConceptConflictStatusException;
use App\Exceptions\NotAllowed\UserNotAllowedException;
use App\Exceptions\Validation\ConceptEndDateBeforeStartException;
use App\Exceptions\Validation\ConceptEndDateBeforeTodayException;
use App\Exceptions\Validation\ConceptEndDateTooFarException;
use App\Exceptions\Validation\ConceptExpiredException;
use App\Exceptions\Validation\ConceptInactiveException;
use App\Exceptions\Validation\ConceptInvalidAmountException;
use App\Exceptions\Validation\ConceptInvalidEndDateException;
use App\Exceptions\Validation\ConceptInvalidStartDateException;
use App\Exceptions\Validation\ConceptMissingNameException;
use App\Exceptions\Validation\ConceptNotStartedException;
use App\Exceptions\Validation\ConceptStartDateTooEarlyException;
use App\Exceptions\Validation\ConceptStartDateTooFarException;
use App\Core\Application\DTO\Request\PaymentConcept\CreatePaymentConceptDTO;
use App\Exceptions\Conflict\ConceptAppliesToConflictException;
use App\Exceptions\Conflict\StudentsAndExceptionsOverlapException;

class PaymentConceptValidator{

    public static function ensureConceptIsActiveAndValid(PaymentConcept $concept, User $user)
    {
        self::ensureConceptIsPayable($concept);

        if ($concept->hasExceptionForUser($user->id)) {
            throw new UserExplicitlyExcludedException();
        }

        if (!self::userIsAllowedForConcept($concept, $user)) {
            throw new UserNotAllowedException();
        }
    }

    private static function ensureConceptIsPayable(PaymentConcept $concept): void
    {
        if (!$concept->isActive()) {
            throw new ConceptInactiveException();
        }

        if (!$concept->hasStarted()) {
            throw new ConceptNotStartedException('El concepto no ha iniciado.');
        }

        if ($concept->isExpired()) {
            throw new ConceptExpiredException();
        }
    }

    private static function userIsAllowedForConcept(PaymentConcept $concept, User $user): bool
    {
        $student = $user->getStudentDetail();

        if ($concept->applies_to === PaymentConceptAppliesTo::TODOS  && $user->hasRole(UserRoles::STUDENT->value)) {
            return true;
        }

        if ($concept->hasUser($user->id)) {
            return true;
        }

        if ($student && $concept->hasCareer($student->career_id)) {
            return true;
        }

        if ($student && $concept->hasSemester($student->semestre)) {
            return true;
        }

        if ($user->isApplicant() && $concept->hasTag(PaymentConceptApplicantType::APPLICANT)) {
            return true;
        }

        if ($user->isNewStudent() && $concept->hasTag(PaymentConceptApplicantType::NO_STUDENT_DETAILS)) {
            return true;
        }

        return false;
    }


    public static function ensureConceptHasStarted(PaymentConcept $concept)
    {
        if (!$concept->hasStarted()) {
            throw new ConceptNotStartedException('El concepto no ha iniciado, no puede ser finalizado.');
        }
    }

    public static function ensureValidStatusTransition(PaymentConcept $concept, PaymentConceptStatus $newStatus)
    {

        $current = $concept->status;
        if ($current === $newStatus) {
           throw match ($newStatus) {
                PaymentConceptStatus::ACTIVO       => new ConceptAlreadyActiveException(),
                PaymentConceptStatus::FINALIZADO   => new ConceptAlreadyFinalizedException(),
                PaymentConceptStatus::DESACTIVADO  => new ConceptAlreadyDisabledException(),
                PaymentConceptStatus::ELIMINADO    => new ConceptAlreadyDeletedException(),
            };
        }

        if (!$current->canTransitionTo($newStatus)) {
            throw match (true) {
                $current === PaymentConceptStatus::FINALIZADO
                    && $newStatus === PaymentConceptStatus::DESACTIVADO
                    => new ConceptCannotBeDisabledException(),
                $current === PaymentConceptStatus::ELIMINADO
                    && $newStatus === PaymentConceptStatus::FINALIZADO
                    => new ConceptCannotBeFinalizedException(),
                default => new ConceptConflictStatusException(
                    "No se puede cambiar el estado de {$current->value} a {$newStatus->value}."
                ),
            };
        }
    }

    public static function ensureConceptIsValidToUpdate(PaymentConcept $concept){
        if (!$concept->status->isUpdatable()) {
            throw new ConceptCannotBeUpdatedException();
        }
    }

    public static function ensureConsistencyAppliesToToUpdate(UpdatePaymentConceptRelationsDTO $dto, PaymentConcept $concept){
        if (!$concept->status->isUpdatable()) {
            throw new ConceptCannotBeUpdatedException();
        }
        if ($dto->appliesTo === PaymentConceptAppliesTo::ESTUDIANTES
            && !empty($dto->exceptionStudents)
        ) {
            throw new ValidationException(
                'No se pueden agregar excepciones cuando el concepto aplica a estudiantes específicos'
            );
        }
        if ($concept->applies_to === PaymentConceptAppliesTo::ESTUDIANTES
            && !empty($dto->exceptionStudents)
        ) {
            throw new ValidationException(
                'No se pueden agregar excepciones a un concepto que ya aplica a estudiantes específicos'
            );
        }
    }

    public static function ensureCreatePaymentDTOIsValid(CreatePaymentConceptDTO $dto)
    {
        self::appliesToConflictAndOverlap($dto);

        if(!in_array($dto->status, PaymentConceptStatus::allowedStatusesToCreateConcept(), true))
        {
            throw new ValidationException("No puedes crear un concepto con estatus {$dto->status->value}, solo se permiten: " .
            implode(', ', array_map(fn($s) => $s->value, PaymentConceptStatus::allowedStatusesToCreateConcept())));
        }

        if ($dto->appliesTo === PaymentConceptAppliesTo::ESTUDIANTES
            && !empty($dto->exceptionStudents)
        ) {
            throw new ValidationException(
                'No se pueden agregar excepciones cuando el concepto aplica a estudiantes específicos'
            );
        }
        if ($dto->appliesTo) {
            self::validateAppliesToConsistency($dto);
        }
    }

    private static function appliesToConflictAndOverlap(CreatePaymentConceptDTO|UpdatePaymentConceptRelationsDTO $dto){
        if (($dto->appliesTo === PaymentConceptAppliesTo::TODOS) && (!empty($dto->careers) || !empty($dto->semesters) || !empty($dto->students) || !empty($dto->applicantTags)) ||
            ($dto->students) && (!empty($dto->semesters) || !empty($dto->applicantTags) || !empty($dto->careers)) ||
            ($dto->applicantTags) && (!empty($dto->semesters) || !empty($dto->careers) || !empty($dto->students)) ||
            ($dto->semesters) && (!empty($dto->applicantTags) || !empty($dto->students)) ||
            ($dto->careers) && (!empty($dto->applicantTags) || !empty($dto->students))
        ) {
            throw new ConceptAppliesToConflictException();
        }
        if(!empty($dto->students) && !empty($dto->exceptionStudents)){
            $intersection = array_intersect(
                (array)$dto->students,
                (array)$dto->exceptionStudents
            );

            if (!empty($intersection)) {
                throw new StudentsAndExceptionsOverlapException();
            }
        }
    }

    public static function ensureUpdatePaymentConceptDTOIsValid(UpdatePaymentConceptRelationsDTO $dto)
    {
        self::appliesToConflictAndOverlap($dto);

        if ($dto->removeAllExceptions) {
            if (!empty($dto->exceptionStudents)) {
                throw new RemoveExceptionsAndExceptionStudentsOverlapException(
                    'No se puede enviar removeAllExceptions y exceptionStudents simultáneamente'
                );
            }

            if ($dto->replaceExceptions) {
                throw new RemoveExceptionsAndExceptionStudentsOverlapException(
                    'No se puede enviar removeAllExceptions y replaceExceptions simultáneamente'
                );
            }

        }
        if($dto->appliesTo === PaymentConceptAppliesTo::ESTUDIANTES && !empty($dto->exceptionStudents))
        {
            throw new ValidationException(
                'No se pueden agregar excepciones cuando el concepto aplica a estudiantes específicos'
            );
        }
        if ($dto->appliesTo) {
            self::validateAppliesToConsistency($dto);
        }
    }

    private static function validateAppliesToConsistency(UpdatePaymentConceptRelationsDTO|CreatePaymentConceptDTO $dto): void
    {
        $appliesTo = $dto->appliesTo;

        switch ($appliesTo) {
            case PaymentConceptAppliesTo::CARRERA:
                if (empty($dto->careers)) {
                    throw new RequiredForAppliesToException('Debes agregar carreras si es un concepto aplicable a carrera');
                }
                break;

            case PaymentConceptAppliesTo::SEMESTRE:
                if (empty($dto->semesters)) {
                    throw new RequiredForAppliesToException('Debes agregar semestres si es un concepto aplicable a semestre');
                }
                break;

            case PaymentConceptAppliesTo::ESTUDIANTES:
                if (empty($dto->students)) {
                    throw new RequiredForAppliesToException('Desbes agregar estudiantes si es un concepto aplicable a estudiante');
                }
                break;

            case PaymentConceptAppliesTo::CARRERA_SEMESTRE:
                if (empty($dto->careers) || empty($dto->semesters)) {
                    throw new RequiredForAppliesToException('Debes agregar carreras y semestres si es un concepto aplicable a carrera-semestre');
                }
                break;

            case PaymentConceptAppliesTo::TAG:
                if (empty($dto->applicantTags)) {
                    throw new RequiredForAppliesToException('Debes agregar tags si es un concepto aplicable a casos especiales');
                }
                break;

            case PaymentConceptAppliesTo::TODOS:
                break;
        }
    }

    public static function ensureConceptHasRequiredFields(PaymentConcept $concept)
    {
        $minAmount = config('concepts.amount.min');
        $maxAmount = config('concepts.amount.max');
        if (empty($concept->concept_name)) {
            throw new ConceptMissingNameException();
        }
        if ($concept->amount === null ||bccomp($concept->amount, $minAmount, 2) === -1 ||
            bccomp($concept->amount, $maxAmount, 2) === 1) {
            throw new ConceptInvalidAmountException();
        }

        if (!$concept->start_date instanceof \Carbon\Carbon) {
            throw new ConceptInvalidStartDateException();
        }

        $today = today();
        $oneMonthBefore = $today->clone()->subMonth();
        $oneMonthAfter  = $today->clone()->addMonth();

        if ($concept->start_date->gt($oneMonthAfter)) {
            throw new ConceptStartDateTooFarException();
        }

        if ($concept->start_date->lt($oneMonthBefore)) {
            throw new ConceptStartDateTooEarlyException();
        }

        if ($concept->end_date === null) {
            return;
        }

        if (!$concept->end_date instanceof \Carbon\Carbon) {
            throw new ConceptInvalidEndDateException();
        }

        if ($concept->end_date->lt($concept->start_date)) {
            throw new ConceptEndDateBeforeStartException();
        }

        if ($concept->end_date->lt($today)) {
            throw new ConceptEndDateBeforeTodayException();
        }

        if ($concept->end_date->gt($concept->start_date->clone()->addYears(5))) {
            throw new ConceptEndDateTooFarException();
        }
    }

    public static function ensureUpdatedFieldsAreValid(PaymentConcept $original, array $fieldsToUpdate): void
    {
        $minAmount = config('concepts.amount.min');
        $maxAmount = config('concepts.amount.max');
        if (isset($fieldsToUpdate['amount'])) {
            $isBelowMin = bccomp($fieldsToUpdate['amount'], $minAmount, 2) === -1;
            $isAboveMax = bccomp($fieldsToUpdate['amount'], $maxAmount, 2) === 1;

            if ($isBelowMin || $isAboveMax) {
                throw new ConceptInvalidAmountException();
            }
        }

        if (isset($fieldsToUpdate['start_date'])) {
            $today = today();
            $oneMonthBefore = $today->clone()->subMonth();
            $oneMonthAfter = $today->clone()->addMonth();

            if ($fieldsToUpdate['start_date']->gt($oneMonthAfter)) {
                throw new ConceptStartDateTooFarException();
            }

            if ($fieldsToUpdate['start_date']->lt($oneMonthBefore)) {
                throw new ConceptStartDateTooEarlyException();
            }
        }

        if (isset($fieldsToUpdate['end_date'])) {
            $startDate = $fieldsToUpdate['start_date'] ?? $original->start_date;

            if ($fieldsToUpdate['end_date']->lt($startDate)) {
                throw new ConceptEndDateBeforeStartException();
            }

            if ($fieldsToUpdate['end_date']->lt(today())) {
                throw new ConceptEndDateBeforeTodayException();
            }

            if ($fieldsToUpdate['end_date']->gt($startDate->clone()->addYears(5))) {
                throw new ConceptEndDateTooFarException();
            }
        }
    }
}
