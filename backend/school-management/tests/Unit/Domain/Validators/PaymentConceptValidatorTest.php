<?php

namespace Tests\Unit\Domain\Validators;

use App\Core\Application\DTO\Request\PaymentConcept\CreatePaymentConceptDTO;
use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptRelationsDTO;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Entities\StudentDetail;
use App\Core\Domain\Utils\Validators\PaymentConceptValidator;
use App\Exceptions\Conflict\ConceptAlreadyActiveException;
use App\Exceptions\Conflict\ConceptAlreadyFinalizedException;
use App\Exceptions\Conflict\ConceptAppliesToConflictException;
use App\Exceptions\Conflict\ConceptCannotBeUpdatedException;
use App\Exceptions\Conflict\RemoveExceptionsAndExceptionStudentsOverlapException;
use App\Exceptions\Conflict\StudentsAndExceptionsOverlapException;
use App\Exceptions\Conflict\UserExplicitlyExcludedException;
use App\Exceptions\Validation\ConceptEndDateBeforeStartException;
use App\Exceptions\Validation\ConceptExpiredException;
use App\Exceptions\Validation\ConceptInactiveException;
use App\Exceptions\Validation\ConceptInvalidAmountException;
use App\Exceptions\Validation\ConceptInvalidStartDateException;
use App\Exceptions\Validation\ConceptMissingNameException;
use App\Exceptions\Validation\ConceptNotStartedException;
use App\Exceptions\Validation\RequiredForAppliesToException;
use App\Exceptions\Validation\ValidationException;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class PaymentConceptValidatorTest extends TestCase
{
    private function createMockPaymentConcept(array $properties = []): MockObject
    {
        $mock = $this->createMock(PaymentConcept::class);

        $defaults = [
            'status' => PaymentConceptStatus::ACTIVO,
            'applies_to' => PaymentConceptAppliesTo::TODOS,
            'concept_name' => 'Test Concept',
            'amount' => '100.00',
            'start_date' => Carbon::now()->subDay(),
            'end_date' => Carbon::now()->addMonth(),
            'userIds' => [],
            'careerIds' => [],
            'semesters' => [],
            'exceptionUserIds' => [],
            'applicantTags' => [],
            'id' => 1,
            'description' => null,
        ];

        $properties = array_merge($defaults, $properties);

        // Configurar métodos principales
        $mock->method('isActive')->willReturn(
            $properties['status'] === PaymentConceptStatus::ACTIVO
        );

        $mock->method('hasStarted')->willReturn(
            $properties['hasStarted'] ?? (Carbon::today() >= $properties['start_date'])
        );

        $mock->method('isExpired')->willReturn(
            $properties['isExpired'] ?? ($properties['end_date'] && Carbon::today() > $properties['end_date'])
        );

        $mock->status = $properties['status'];
        $mock->applies_to = $properties['applies_to'];
        $mock->concept_name = $properties['concept_name'];
        $mock->amount = $properties['amount'];
        $mock->start_date = $properties['start_date'];
        $mock->end_date = $properties['end_date'];
        $mock->id = $properties['id'];
        $mock->description = $properties['description'];

        $mock->method('hasExceptionForUser')->willReturnCallback(
            fn($userId) => in_array($userId, $properties['exceptionUserIds'])
        );

        $mock->method('hasUser')->willReturnCallback(
            fn($userId) => in_array($userId, $properties['userIds'])
        );

        $mock->method('hasCareer')->willReturnCallback(
            fn($careerId) => in_array($careerId, $properties['careerIds'])
        );

        $mock->method('hasSemester')->willReturnCallback(
            fn($semester) => in_array((string) $semester, array_map('strval', $properties['semesters']), true)
        );

        $mock->method('hasTag')->willReturnCallback(
            function($tag) use ($properties) {
                $tagValue = $tag instanceof \App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType
                    ? $tag->value
                    : $tag;
                return in_array($tagValue, $properties['applicantTags'], true);
            }
        );

        return $mock;
    }

    private function createMockUser(array $properties = []): MockObject
    {
        $mock = $this->createMock(User::class);

        $defaults = [
            'id' => 1,
            'roles' => [],
            'studentDetail' => null,
        ];

        $properties = array_merge($defaults, $properties);


        $mock->method('hasRole')->willReturnCallback(
            function($roleName) use ($properties) {
                return in_array($roleName, array_map(fn($role) => $role->value, $properties['roles']), true);
            }
        );

        $mock->studentDetail = $properties['studentDetail'];

        $mock->method('getStudentDetail')->willReturn($properties['studentDetail']);

        $mock->method('isApplicant')->willReturn(
            in_array(UserRoles::APPLICANT, $properties['roles'])
        );

        $mock->method('isNewStudent')->willReturnCallback(
            function() use ($properties) {
                $isStudent = in_array(UserRoles::STUDENT, $properties['roles']);
                return $isStudent && !$properties['studentDetail'];
            }
        );
        $mock->id = $properties['id'];

        return $mock;
    }

    private function createMockStudentDetail(array $properties = []): StudentDetail
    {
        return new StudentDetail(
            user_id: $properties['user_id'] ?? 1,
            career_id: $properties['career_id'] ?? 1,
            semestre: $properties['semestre'] ?? 5
        );
    }

    // Tests para ensureConceptIsActiveAndValid
    #[Test]
    public function ensureConceptIsActiveAndValid_passes_when_all_conditions_met(): void
    {
        $concept = $this->createMockPaymentConcept([]);

        $studentDetail = $this->createMockStudentDetail([]);

        $user = $this->createMockUser([
            'roles' => [UserRoles::STUDENT],
            'studentDetail' => $studentDetail,
        ]);

        $this->expectNotToPerformAssertions();
        PaymentConceptValidator::ensureConceptIsActiveAndValid($concept, $user);
    }

    #[Test]
    public function ensureConceptIsActiveAndValid_throws_when_concept_inactive(): void
    {
        $concept = $this->createMockPaymentConcept([
            'status' => PaymentConceptStatus::DESACTIVADO,
        ]);

        $user = $this->createMockUser();

        $this->expectException(ConceptInactiveException::class);
        PaymentConceptValidator::ensureConceptIsActiveAndValid($concept, $user);
    }

    #[Test]
    public function ensureConceptIsActiveAndValid_throws_when_concept_not_started(): void
    {
        $concept = $this->createMockPaymentConcept([
            'start_date' => Carbon::now()->addDays(10),
            'hasStarted' => false,
        ]);

        $user = $this->createMockUser();

        $this->expectException(ConceptNotStartedException::class);
        $this->expectExceptionMessage('El concepto no ha iniciado.');
        PaymentConceptValidator::ensureConceptIsActiveAndValid($concept, $user);
    }

    #[Test]
    public function ensureConceptIsActiveAndValid_throws_when_concept_expired(): void
    {
        $concept = $this->createMockPaymentConcept([
            'end_date' => Carbon::now()->subDays(10),
            'isExpired' => true,
        ]);

        $user = $this->createMockUser();

        $this->expectException(ConceptExpiredException::class);
        PaymentConceptValidator::ensureConceptIsActiveAndValid($concept, $user);
    }

    #[Test]
    public function ensureConceptIsActiveAndValid_throws_when_user_explicitly_excluded(): void
    {
        $concept = $this->createMockPaymentConcept([
            'exceptionUserIds' => [1],
        ]);

        $user = $this->createMockUser(['id' => 1]);

        $this->expectException(UserExplicitlyExcludedException::class);
        PaymentConceptValidator::ensureConceptIsActiveAndValid($concept, $user);
    }

    #[Test]
    public function ensureConceptIsActiveAndValid_throws_when_user_not_allowed(): void
    {
        $concept = $this->createMockPaymentConcept([
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES,
            'userIds' => [2, 3], // No incluye al usuario 1
        ]);

        $user = $this->createMockUser([
            'id' => 1,
            'roles' => [UserRoles::STUDENT],
        ]);

        $this->expectException(\App\Exceptions\NotAllowed\UserNotAllowedException::class);
        PaymentConceptValidator::ensureConceptIsActiveAndValid($concept, $user);
    }

    #[Test]
    public function ensureConceptIsActiveAndValid_passes_when_user_in_concept(): void
    {
        $concept = $this->createMockPaymentConcept([
            'userIds' => [1, 2, 3],
        ]);

        $user = $this->createMockUser([
            'id' => 1,
            'roles' => [UserRoles::STUDENT],
        ]);

        $this->expectNotToPerformAssertions();
        PaymentConceptValidator::ensureConceptIsActiveAndValid($concept, $user);
    }

    #[Test]
    public function ensureConceptIsActiveAndValid_passes_when_user_in_career(): void
    {
        $concept = $this->createMockPaymentConcept([
            'careerIds' => [1, 2],
            'applies_to' => PaymentConceptAppliesTo::CARRERA,
        ]);

        $studentDetail = $this->createMockStudentDetail(['career_id' => 1]);

        $user = $this->createMockUser([
            'id' => 1,
            'roles' => [UserRoles::STUDENT],
            'studentDetail' => $studentDetail,
        ]);

        $this->expectNotToPerformAssertions();
        PaymentConceptValidator::ensureConceptIsActiveAndValid($concept, $user);
    }

    // Tests para ensureConceptHasStarted
    #[Test]
    public function ensureConceptHasStarted_passes_when_concept_has_started(): void
    {
        $concept = $this->createMockPaymentConcept([
            'hasStarted' => true,
        ]);

        $this->expectNotToPerformAssertions();
        PaymentConceptValidator::ensureConceptHasStarted($concept);
    }

    #[Test]
    public function ensureConceptHasStarted_throws_when_concept_not_started(): void
    {
        $concept = $this->createMockPaymentConcept([
            'hasStarted' => false,
        ]);

        $this->expectException(ConceptNotStartedException::class);
        $this->expectExceptionMessage('El concepto no ha iniciado, no puede ser finalizado.');
        PaymentConceptValidator::ensureConceptHasStarted($concept);
    }

    // Tests para ensureValidStatusTransition
    #[Test]
    public function ensureValidStatusTransition_throws_when_same_status_active(): void
    {
        $concept = $this->createMockPaymentConcept([
            'status' => PaymentConceptStatus::ACTIVO,
        ]);

        $this->expectException(ConceptAlreadyActiveException::class);
        PaymentConceptValidator::ensureValidStatusTransition($concept, PaymentConceptStatus::ACTIVO);
    }

    #[Test]
    public function ensureValidStatusTransition_throws_when_same_status_finalizado(): void
    {
        $concept = $this->createMockPaymentConcept([
            'status' => PaymentConceptStatus::FINALIZADO,
        ]);

        $this->expectException(ConceptAlreadyFinalizedException::class);
        PaymentConceptValidator::ensureValidStatusTransition($concept, PaymentConceptStatus::FINALIZADO);
    }

    #[Test]
    public function ensureValidStatusTransition_passes_when_transition_allowed(): void
    {
        $concept = $this->createMockPaymentConcept([
            'status' => PaymentConceptStatus::ACTIVO,
        ]);

        $this->expectNotToPerformAssertions();
        PaymentConceptValidator::ensureValidStatusTransition($concept, PaymentConceptStatus::FINALIZADO);
    }

    #[Test]
    public function ensureValidStatusTransition_throws_when_transition_not_allowed(): void
    {
        $concept = $this->createMockPaymentConcept([
            'status' => PaymentConceptStatus::FINALIZADO,
        ]);

        $this->expectException(\App\Exceptions\Conflict\ConceptCannotBeDisabledException::class);
        PaymentConceptValidator::ensureValidStatusTransition($concept, PaymentConceptStatus::DESACTIVADO);
    }

    // Tests para ensureConceptIsValidToUpdate
    #[Test]
    public function ensureConceptIsValidToUpdate_passes_when_updatable(): void
    {
        $concept = $this->createMockPaymentConcept([
            'status' => PaymentConceptStatus::ACTIVO,
        ]);

        $this->expectNotToPerformAssertions();
        PaymentConceptValidator::ensureConceptIsValidToUpdate($concept);
    }

    #[Test]
    public function ensureConceptIsValidToUpdate_throws_when_not_updatable(): void
    {
        $concept = $this->createMockPaymentConcept([
            'status' => PaymentConceptStatus::FINALIZADO,
        ]);

        $this->expectException(ConceptCannotBeUpdatedException::class);
        PaymentConceptValidator::ensureConceptIsValidToUpdate($concept);
    }

    // Tests para ensureCreatePaymentDTOIsValid
    #[Test]
    public function ensureCreatePaymentDTOIsValid_passes_with_valid_data(): void
    {
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Test',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::CARRERA,
            description: 'Test Description',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            careers: [1, 2, 3]
        );

        $this->expectNotToPerformAssertions();
        PaymentConceptValidator::ensureCreatePaymentDTOIsValid($dto);
    }

    #[Test]
    public function ensureCreatePaymentDTOIsValid_throws_when_applies_to_conflict(): void
    {
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Test',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::TODOS,
            description: 'Test Description',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            careers: [1, 2] // Conflicto: es global pero tiene carreras
        );

        $this->expectException(ConceptAppliesToConflictException::class);
        PaymentConceptValidator::ensureCreatePaymentDTOIsValid($dto);
    }

    #[Test]
    public function ensureCreatePaymentDTOIsValid_throws_when_students_and_exceptions_overlap(): void
    {
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Test',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::ESTUDIANTES,
            description: 'Test Description',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            students: [1, 2, 3],
            exceptionStudents: [3, 4, 5] // El 3 está en ambos
        );

        $this->expectException(StudentsAndExceptionsOverlapException::class);
        PaymentConceptValidator::ensureCreatePaymentDTOIsValid($dto);
    }

    // Tests para ensureUpdatePaymentConceptDTOIsValid
    #[Test]
    public function ensureUpdatePaymentConceptDTOIsValid_passes_with_valid_data(): void
    {
        $dto = new UpdatePaymentConceptRelationsDTO(
            id: 1,
            semesters: [1, 2],
            appliesTo: PaymentConceptAppliesTo::SEMESTRE
        );

        $this->expectNotToPerformAssertions();
        PaymentConceptValidator::ensureUpdatePaymentConceptDTOIsValid($dto);
    }

    #[Test]
    public function ensureUpdatePaymentConceptDTOIsValid_throws_when_removeAllExceptions_with_exceptionStudents(): void
    {
        $dto = new UpdatePaymentConceptRelationsDTO(
            id: 1,
            appliesTo: PaymentConceptAppliesTo::TODOS,
            exceptionStudents: [1, 2],
            removeAllExceptions: true
        );

        $this->expectException(RemoveExceptionsAndExceptionStudentsOverlapException::class);
        $this->expectExceptionMessage('No se puede enviar removeAllExceptions y exceptionStudents simultáneamente');
        PaymentConceptValidator::ensureUpdatePaymentConceptDTOIsValid($dto);
    }

    #[Test]
    public function ensureUpdatePaymentConceptDTOIsValid_throws_when_removeAllExceptions_with_replaceExceptions(): void
    {
        $dto = new UpdatePaymentConceptRelationsDTO(
            id: 1,
            appliesTo: PaymentConceptAppliesTo::TODOS,
            replaceExceptions: true,
            removeAllExceptions: true
        );

        $this->expectException(RemoveExceptionsAndExceptionStudentsOverlapException::class);
        $this->expectExceptionMessage('No se puede enviar removeAllExceptions y replaceExceptions simultáneamente');
        PaymentConceptValidator::ensureUpdatePaymentConceptDTOIsValid($dto);
    }

    // Tests para validateAppliesToConsistency usando reflection
    #[Test]
    public function validateAppliesToConsistency_passes_for_todos(): void
    {
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Test',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::TODOS,
            description: null,
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth()
        );

        $reflection = new \ReflectionClass(PaymentConceptValidator::class);
        $method = $reflection->getMethod('validateAppliesToConsistency');
        $method->setAccessible(true);

        $this->expectNotToPerformAssertions();
        $method->invoke(null, $dto);
    }

    #[Test]
    public function validateAppliesToConsistency_throws_when_careers_empty_for_carrera(): void
    {
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Test',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::CARRERA,
            description: null,
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth()
        // careers no está definido
        );

        $reflection = new \ReflectionClass(PaymentConceptValidator::class);
        $method = $reflection->getMethod('validateAppliesToConsistency');
        $method->setAccessible(true);

        $this->expectException(RequiredForAppliesToException::class);
        $this->expectExceptionMessage('Debes agregar carreras si es un concepto aplicable a carrera');
        $method->invoke(null, $dto);
    }

    #[Test]
    public function validateAppliesToConsistency_throws_when_semesters_empty_for_semestre(): void
    {
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Test',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::SEMESTRE,
            description: null,
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth()
        // semesters no está definido
        );

        $reflection = new \ReflectionClass(PaymentConceptValidator::class);
        $method = $reflection->getMethod('validateAppliesToConsistency');
        $method->setAccessible(true);

        $this->expectException(RequiredForAppliesToException::class);
        $this->expectExceptionMessage('Debes agregar semestres si es un concepto aplicable a semestre');
        $method->invoke(null, $dto);
    }

    #[Test]
    public function validateAppliesToConsistency_throws_when_students_empty_for_estudiantes(): void
    {
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Test',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::ESTUDIANTES,
            description: null,
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth()
        // students no está definido
        );

        $reflection = new \ReflectionClass(PaymentConceptValidator::class);
        $method = $reflection->getMethod('validateAppliesToConsistency');
        $method->setAccessible(true);

        $this->expectException(RequiredForAppliesToException::class);
        $this->expectExceptionMessage('Desbes agregar estudiantes si es un concepto aplicable a estudiante');
        $method->invoke(null, $dto);
    }

    #[Test]
    public function validateAppliesToConsistency_throws_when_careers_empty_for_carrera_semestre(): void
    {
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Test',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::CARRERA_SEMESTRE,
            description: null,
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            semesters: [1, 2]
        // careers no está definido
        );

        $reflection = new \ReflectionClass(PaymentConceptValidator::class);
        $method = $reflection->getMethod('validateAppliesToConsistency');
        $method->setAccessible(true);

        $this->expectException(RequiredForAppliesToException::class);
        $this->expectExceptionMessage('Debes agregar carreras y semestres si es un concepto aplicable a carrera-semestre');
        $method->invoke(null, $dto);
    }

    #[Test]
    public function validateAppliesToConsistency_throws_when_tags_empty_for_tag(): void
    {
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Test',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::TAG,
            description: null,
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth()
        // applicantTags no está definido
        );

        $reflection = new \ReflectionClass(PaymentConceptValidator::class);
        $method = $reflection->getMethod('validateAppliesToConsistency');
        $method->setAccessible(true);

        $this->expectException(RequiredForAppliesToException::class);
        $this->expectExceptionMessage('Debes agregar tags si es un concepto aplicable a casos especiales');
        $method->invoke(null, $dto);
    }

    // Tests para ensureConceptHasRequiredFields
    #[Test]
    public function ensureConceptHasRequiredFields_passes_with_valid_data(): void
    {
        config(['concepts.amount.min' => '10.00']);
        config(['concepts.amount.max' => '1000.00']);

        $concept = $this->createMockPaymentConcept([
            'concept_name' => 'Valid Concept',
            'amount' => '100.00',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonth(),
        ]);

        $this->expectNotToPerformAssertions();
        PaymentConceptValidator::ensureConceptHasRequiredFields($concept);
    }

    #[Test]
    public function ensureConceptHasRequiredFields_throws_when_missing_name(): void
    {
        $concept = $this->createMockPaymentConcept(['concept_name' => '']);

        $this->expectException(ConceptMissingNameException::class);
        PaymentConceptValidator::ensureConceptHasRequiredFields($concept);
    }

    #[Test]
    public function ensureConceptHasRequiredFields_throws_when_amount_below_min(): void
    {
        config(['concepts.amount.min' => '10.00']);
        config(['concepts.amount.max' => '1000.00']);

        $concept = $this->createMockPaymentConcept(['amount' => '5.00']);

        $this->expectException(ConceptInvalidAmountException::class);
        PaymentConceptValidator::ensureConceptHasRequiredFields($concept);
    }

    #[Test]
    public function ensureConceptHasRequiredFields_throws_when_amount_above_max(): void
    {
        config(['concepts.amount.min' => '10.00']);
        config(['concepts.amount.max' => '1000.00']);

        $concept = $this->createMockPaymentConcept(['amount' => '2000.00']);

        $this->expectException(ConceptInvalidAmountException::class);
        PaymentConceptValidator::ensureConceptHasRequiredFields($concept);
    }

    #[Test]
    public function ensureConceptHasRequiredFields_throws_when_start_date_too_far(): void
    {
        config(['concepts.amount.min' => '10.00']);
        config(['concepts.amount.max' => '1000.00']);

        $concept = $this->createMockPaymentConcept([
            'concept_name' => 'Test',
            'amount' => '100.00',
            'start_date' => Carbon::now()->addMonths(2), // Más de un mes
        ]);

        $this->expectException(\App\Exceptions\Validation\ConceptStartDateTooFarException::class);
        PaymentConceptValidator::ensureConceptHasRequiredFields($concept);
    }

    #[Test]
    public function ensureConceptHasRequiredFields_throws_when_start_date_too_early(): void
    {
        config(['concepts.amount.min' => '10.00']);
        config(['concepts.amount.max' => '1000.00']);

        $concept = $this->createMockPaymentConcept([
            'concept_name' => 'Test',
            'amount' => '100.00',
            'start_date' => Carbon::now()->subMonths(2), // Más de un mes atrás
        ]);

        $this->expectException(\App\Exceptions\Validation\ConceptStartDateTooEarlyException::class);
        PaymentConceptValidator::ensureConceptHasRequiredFields($concept);
    }

    #[Test]
    public function ensureConceptHasRequiredFields_passes_when_end_date_null(): void
    {
        config(['concepts.amount.min' => '10.00']);
        config(['concepts.amount.max' => '1000.00']);

        $concept = $this->createMockPaymentConcept([
            'concept_name' => 'Valid Concept',
            'amount' => '100.00',
            'start_date' => Carbon::now(),
            'end_date' => null,
        ]);

        $this->expectNotToPerformAssertions();
        PaymentConceptValidator::ensureConceptHasRequiredFields($concept);
    }

    #[Test]
    public function ensureConceptHasRequiredFields_throws_when_end_date_before_start(): void
    {
        config(['concepts.amount.min' => '10.00']);
        config(['concepts.amount.max' => '1000.00']);

        $concept = $this->createMockPaymentConcept([
            'concept_name' => 'Test',
            'amount' => '100.00',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->subDay(),
        ]);

        $this->expectException(ConceptEndDateBeforeStartException::class);
        PaymentConceptValidator::ensureConceptHasRequiredFields($concept);
    }

    // Tests para ensureUpdatedFieldsAreValid
    #[Test]
    public function ensureUpdatedFieldsAreValid_passes_with_valid_fields(): void
    {
        config(['concepts.amount.min' => '10.00']);
        config(['concepts.amount.max' => '1000.00']);

        $original = $this->createMockPaymentConcept([
            'start_date' => Carbon::now(),
        ]);

        $fields = [
            'amount' => '200.00',
            'start_date' => Carbon::now()->addDay(),
            'end_date' => Carbon::now()->addMonths(2),
        ];

        $this->expectNotToPerformAssertions();
        PaymentConceptValidator::ensureUpdatedFieldsAreValid($original, $fields);
    }

    #[Test]
    public function ensureUpdatedFieldsAreValid_throws_when_amount_above_max(): void
    {
        config(['concepts.amount.min' => '10.00']);
        config(['concepts.amount.max' => '1000.00']);

        $original = $this->createMockPaymentConcept();
        $fields = ['amount' => '2000.00'];

        $this->expectException(ConceptInvalidAmountException::class);
        PaymentConceptValidator::ensureUpdatedFieldsAreValid($original, $fields);
    }

    #[Test]
    public function ensureUpdatedFieldsAreValid_passes_when_only_amount_updated(): void
    {
        config(['concepts.amount.min' => '10.00']);
        config(['concepts.amount.max' => '1000.00']);

        $original = $this->createMockPaymentConcept();
        $fields = ['amount' => '150.00'];

        $this->expectNotToPerformAssertions();
        PaymentConceptValidator::ensureUpdatedFieldsAreValid($original, $fields);
    }

    #[Test]
    public function ensureUpdatedFieldsAreValid_passes_when_only_start_date_updated(): void
    {
        $original = $this->createMockPaymentConcept();
        $fields = ['start_date' => Carbon::now()->addDays(5)];

        $this->expectNotToPerformAssertions();
        PaymentConceptValidator::ensureUpdatedFieldsAreValid($original, $fields);
    }

    #[Test]
    public function ensureUpdatedFieldsAreValid_uses_original_start_date_when_updating_end_date(): void
    {
        $startDate = Carbon::now()->subDays(10);
        $original = $this->createMockPaymentConcept(['start_date' => $startDate]);
        $endDate = max($startDate->copy()->addDays(5), Carbon::now()->addDay());
        $fields = ['end_date' => $endDate];
        $this->expectNotToPerformAssertions();
        PaymentConceptValidator::ensureUpdatedFieldsAreValid($original, $fields);
    }

    // Tests para appliesToConflictAndOverlap usando reflection
    #[Test]
    public function appliesToConflictAndOverlap_throws_when_global_with_students(): void
    {
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Test',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::TODOS,
            description: null,
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            students: [1, 2]
        );

        $reflection = new \ReflectionClass(PaymentConceptValidator::class);
        $method = $reflection->getMethod('appliesToConflictAndOverlap');
        $method->setAccessible(true);

        $this->expectException(ConceptAppliesToConflictException::class);
        $method->invoke(null, $dto);
    }

    #[Test]
    public function appliesToConflictAndOverlap_passes_when_no_conflict(): void
    {
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Test',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::TODOS,
            description: null,
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth()
        // Solo global, sin conflictos
        );

        $reflection = new \ReflectionClass(PaymentConceptValidator::class);
        $method = $reflection->getMethod('appliesToConflictAndOverlap');
        $method->setAccessible(true);

        $this->expectNotToPerformAssertions();
        $method->invoke(null, $dto);
    }

    #[Test]
    public function appliesToConflictAndOverlap_throws_when_students_with_semesters(): void
    {
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Test',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::TODOS,
            description: null,
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            semesters: [1, 2],
            students: [1, 2]
        );

        $reflection = new \ReflectionClass(PaymentConceptValidator::class);
        $method = $reflection->getMethod('appliesToConflictAndOverlap');
        $method->setAccessible(true);

        $this->expectException(ConceptAppliesToConflictException::class);
        $method->invoke(null, $dto);
    }

    // Tests para ensureConsistencyAppliesToToUpdate
    #[Test]
    public function ensureConsistencyAppliesToToUpdate_throws_when_concept_applies_to_estudiantes_and_exceptions_provided(): void
    {
        $concept = $this->createMockPaymentConcept([
            'status' => PaymentConceptStatus::ACTIVO,
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES,
        ]);

        $dto = new UpdatePaymentConceptRelationsDTO(
            id: 1,
            appliesTo: PaymentConceptAppliesTo::TODOS,
            exceptionStudents: [1, 2]
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No se pueden agregar excepciones a un concepto que ya aplica a estudiantes específicos');
        PaymentConceptValidator::ensureConsistencyAppliesToToUpdate($dto, $concept);
    }

    #[Test]
    public function ensureCreatePaymentDTOIsValid_throws_when_applies_to_estudiantes_with_exceptions(): void
    {
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Test',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::ESTUDIANTES,
            description: null,
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            students: [1, 2],
            exceptionStudents: [3, 4]
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No se pueden agregar excepciones cuando el concepto aplica a estudiantes específicos');
        PaymentConceptValidator::ensureCreatePaymentDTOIsValid($dto);
    }

    // Tests edge cases
    #[Test]
    public function ensureConceptHasRequiredFields_throws_when_end_date_before_today(): void
    {
        config(['concepts.amount.min' => '10.00']);
        config(['concepts.amount.max' => '1000.00']);

        $concept = $this->createMockPaymentConcept([
            'concept_name' => 'Test',
            'amount' => '100.00',
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->subDays(1),
        ]);

        $this->expectException(\App\Exceptions\Validation\ConceptEndDateBeforeTodayException::class);
        PaymentConceptValidator::ensureConceptHasRequiredFields($concept);
    }

    #[Test]
    public function ensureConceptHasRequiredFields_throws_when_end_date_too_far(): void
    {
        config(['concepts.amount.min' => '10.00']);
        config(['concepts.amount.max' => '1000.00']);

        $concept = $this->createMockPaymentConcept([
            'concept_name' => 'Test',
            'amount' => '100.00',
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addYears(6),
        ]);

        $this->expectException(\App\Exceptions\Validation\ConceptEndDateTooFarException::class);
        PaymentConceptValidator::ensureConceptHasRequiredFields($concept);
    }
}
