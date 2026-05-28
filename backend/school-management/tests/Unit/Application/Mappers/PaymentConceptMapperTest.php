<?php

namespace Tests\Unit\Application\Mappers;

use App\Core\Application\DTO\Request\PaymentConcept\CreatePaymentConceptDTO;
use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptDTO;
use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptRelationsDTO;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptChangeStatusResponse;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptNameAndAmountResponse;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptsToDashboardResponse;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptToDisplay;
use App\Core\Application\DTO\Response\PaymentConcept\CreatePaymentConceptResponse;
use App\Core\Application\DTO\Response\PaymentConcept\PendingPaymentConceptsResponse;
use App\Core\Application\DTO\Response\PaymentConcept\PendingSummaryResponse;
use App\Core\Application\DTO\Response\PaymentConcept\UpdatePaymentConceptRelationsResponse;
use App\Core\Application\DTO\Response\PaymentConcept\UpdatePaymentConceptResponse;
use App\Core\Application\Mappers\PaymentConceptMapper;
use App\Core\Domain\Entities\PaymentConcept as EntitiesPaymentConcept;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use App\Models\PaymentConcept;
use App\Models\StudentDetail;
use ReflectionClass;
use App\Models\User;
use App\Models\Career;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Stripe\Checkout\Session;
use Tests\TestCase;

class PaymentConceptMapperTest extends TestCase
{
    use RefreshDatabase;

    // ==================== TO DOMAIN TESTS ====================

    #[Test]
    public function to_domain_creates_domain_payment_concept_from_dto(): void
    {
        // Arrange
        Carbon::setTestNow('2024-01-15');

        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Annual Fee',
            amount: '500.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::TODOS,
            description: 'Annual tuition fee',
            start_date: Carbon::parse('2024-01-01'),
            end_date: Carbon::parse('2024-12-31'),
            semesters: [],
            careers: [],
            students: [],
            exceptionStudents: [],
            applicantTags: [],
        );

        // Act
        $result = PaymentConceptMapper::toDomain($dto);

        // Assert
        $this->assertInstanceOf(EntitiesPaymentConcept::class, $result);
        $this->assertEquals('Annual Fee', $result->concept_name);
        $this->assertEquals('500.00', $result->amount);
        $this->assertEquals(PaymentConceptStatus::ACTIVO, $result->status);
        $this->assertEquals(PaymentConceptAppliesTo::TODOS, $result->applies_to);
        $this->assertEquals('Annual tuition fee', $result->description);
        $this->assertEquals('2024-01-01', $result->start_date->toDateString());
        $this->assertEquals('2024-12-31', $result->end_date->toDateString());
        $this->assertNull($result->id); // ID should be null for new concepts
    }

    #[Test]
    public function to_domain_with_null_end_date(): void
    {
        // Arrange
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Monthly Fee',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::ESTUDIANTES,
            description: null,
            start_date: Carbon::parse('2024-01-01'),
            end_date: null, // Null end date
            semesters: [],
            careers: [],
            students: [],
            exceptionStudents: [],
            applicantTags: [],
        );

        // Act
        $result = PaymentConceptMapper::toDomain($dto);

        // Assert
        $this->assertInstanceOf(EntitiesPaymentConcept::class, $result);
        $this->assertEquals('Monthly Fee', $result->concept_name);
        $this->assertEquals('100.00', $result->amount);
        $this->assertNull($result->description);
        $this->assertNull($result->end_date); // Should be null
    }

    // ==================== TO DISPLAY TESTS ====================

    #[Test]
    public function to_display_creates_concept_to_display_from_eloquent_model(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'concept_name' => 'Test Concept',
            'status' => PaymentConceptStatus::ACTIVO,
            'amount' => '150.75',
            'applies_to' => PaymentConceptAppliesTo::CARRERA,
            'description' => 'Test description',
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);

        // Create related data
        $user1 = User::factory()->create();
        $studentDetail1 = StudentDetail::factory()->create([
            'user_id' => $user1->id,
            'n_control' => 'S001'
        ]);

        $user2 = User::factory()->create();
        $studentDetail2 = StudentDetail::factory()->create([
            'user_id' => $user2->id,
            'n_control' => 'S002'
        ]);

        $career1 = Career::factory()->create(['career_name' => 'Computer Science']);
        $career2 = Career::factory()->create(['career_name' => 'Engineering']);
        $concept->careers()->attach([$career1->id, $career2->id]);

        $concept->paymentConceptSemesters()->create(['semestre' => 1]);
        $concept->paymentConceptSemesters()->create(['semestre' => 2]);

        $exceptionUser = User::factory()->create();
        $exceptionStudentDetail = StudentDetail::factory()->create([
            'user_id' => $exceptionUser->id,
            'n_control' => 'E001'
        ]);
        $concept->exceptions()->attach($exceptionUser->id);

        $concept->applicantTypes()->create(['tag' => PaymentConceptApplicantType::NO_STUDENT_DETAILS]);
        $concept->applicantTypes()->create(['tag' => PaymentConceptApplicantType::APPLICANT]);

        // Act
        $result = PaymentConceptMapper::toDisplay($concept);

        // Assert
        $this->assertInstanceOf(ConceptToDisplay::class, $result);
        $this->assertEquals($concept->id, $result->id);
        $this->assertEquals('Test Concept', $result->concept_name);
        $this->assertEquals('activo', $result->status); // lowercase
        $this->assertEquals('2024-01-01', $result->start_date);
        $this->assertEquals('150.75', $result->amount);
        $this->assertEquals('carrera', $result->applies_to); // lowercase
        $this->assertEquals('Test description', $result->description);
        $this->assertEquals('2024-12-31', $result->end_date);

        // Check collections - estos deben estar vacíos porque el mapper está usando studentDetail?->n_control
        // pero el factory no está creando las relaciones cargadas
        $this->assertEmpty($result->users);
        $this->assertEquals([$career1->id, $career2->id], $result->careers);
        $this->assertEquals([1, 2], $result->semesters);
        $this->assertEquals([$exceptionUser->studentDetail->n_control],$result->exceptionUsers);
        $this->assertEquals([PaymentConceptApplicantType::APPLICANT, PaymentConceptApplicantType::NO_STUDENT_DETAILS], $result->applicantTags);
    }

    #[Test]
    public function to_display_with_null_values_and_empty_relations(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'concept_name' => 'Empty Concept',
            'status' => PaymentConceptStatus::ACTIVO,
            'amount' => '100.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS,
            'description' => null,
            'start_date' => '2024-01-01',
            'end_date' => null,
        ]);

        // No relations attached

        // Act
        $result = PaymentConceptMapper::toDisplay($concept);

        // Assert
        $this->assertInstanceOf(ConceptToDisplay::class, $result);
        $this->assertEquals($concept->id, $result->id);
        $this->assertEquals('Empty Concept', $result->concept_name);
        $this->assertEquals('activo', $result->status); // lowercase
        $this->assertEquals('2024-01-01', $result->start_date);
        $this->assertEquals('100.00', $result->amount);
        $this->assertEquals('todos', $result->applies_to); // lowercase
        $this->assertNull($result->description);
        $this->assertNull($result->end_date);

        // Empty collections
        $this->assertEmpty($result->users);
        $this->assertEmpty($result->careers);
        $this->assertEmpty($result->semesters);
        $this->assertEmpty($result->exceptionUsers);
        $this->assertEmpty($result->applicantTags);
    }

    #[Test]
    public function to_display_filters_null_student_details(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create();

        // User without studentDetail
        $userWithoutDetail = User::factory()->create();

        // User with studentDetail but no n_control
        $userWithDetailNoControl = User::factory()->create();
        StudentDetail::factory()->create([
            'user_id' => $userWithDetailNoControl->id,
            'n_control' => null
        ]);

        // User with studentDetail and n_control
        $userWithDetail = User::factory()->create();
        StudentDetail::factory()->create([
            'user_id' => $userWithDetail->id,
            'n_control' => 'S123'
        ]);

        $concept->users()->attach([$userWithoutDetail->id, $userWithDetailNoControl->id, $userWithDetail->id]);

        // Act
        $result = PaymentConceptMapper::toDisplay($concept);

        // Assert - Only user with n_control should be included
        $this->assertEquals(['S123'], $result->users);
    }

    // ==================== TO CREATE CONCEPT DTO TESTS ====================

    #[Test]
    public function to_create_concept_dto_creates_dto_from_array(): void
    {
        // Arrange
        $data = [
            'concept_name' => 'New Concept',
            'amount' => 250.50,
            'status' => 'activo', // lowercase
            'applies_to' => 'estudiantes', // lowercase
            'description' => 'Test description',
            'start_date' => '2024-02-01',
            'end_date' => '2024-06-30',
            'semestres' => ['2024-1', '2024-2'],
            'careers' => [1, 2],
            'students' => [101, 102],
            'exceptionStudents' => [201],
            'applicantTags' => ['NEW', 'REGULAR'],
        ];

        // Act
        $result = PaymentConceptMapper::toCreateConceptDTO($data);

        // Assert
        $this->assertInstanceOf(CreatePaymentConceptDTO::class, $result);
        $this->assertEquals('New Concept', $result->concept_name);
        $this->assertEquals('250.50', $result->amount); // Formatted to 2 decimals
        $this->assertEquals(PaymentConceptStatus::ACTIVO, $result->status);
        $this->assertEquals(PaymentConceptAppliesTo::ESTUDIANTES, $result->appliesTo);
        $this->assertEquals('Test description', $result->description);
        $this->assertEquals('2024-02-01', $result->start_date->toDateString());
        $this->assertEquals('2024-06-30', $result->end_date->toDateString());
        $this->assertEquals(['2024-1', '2024-2'], $result->semesters);
        $this->assertEquals([1, 2], $result->careers);
        $this->assertEquals([101, 102], $result->students);
        $this->assertEquals([201], $result->exceptionStudents);
        $this->assertEquals(['NEW', 'REGULAR'], $result->applicantTags);
    }

    #[Test]
    public function to_create_concept_dto_with_defaults_when_fields_missing(): void
    {
        // Arrange - Minimal data
        $data = [
            'concept_name' => 'Minimal Concept',
            'amount' => 100,
            // status missing - should default to ACTIVO
            // applies_to missing - should default to TODOS
            // description missing
            // start_date missing
            // end_date missing
            'semestres' => [],
            'careers' => [],
            'students' => [],
            'exceptionStudents' => [],
            'applicantTags' => [],
        ];

        // Act
        $result = PaymentConceptMapper::toCreateConceptDTO($data);

        // Assert
        $this->assertInstanceOf(CreatePaymentConceptDTO::class, $result);
        $this->assertEquals('Minimal Concept', $result->concept_name);
        $this->assertEquals('100.00', $result->amount); // Formatted
        $this->assertEquals(PaymentConceptStatus::ACTIVO, $result->status); // Default
        $this->assertEquals(PaymentConceptAppliesTo::TODOS, $result->appliesTo); // Default
        $this->assertNull($result->description);
        $this->assertNull($result->start_date);
        $this->assertNull($result->end_date);
    }

    #[Test]
    public function to_create_concept_dto_case_insensitive_enum_mapping(): void
    {
        $testCases = [
            ['status' => 'ACTIVO', 'applies_to' => 'TODOS'],
            ['status' => 'activo', 'applies_to' => 'todos'], // lowercase
            ['status' => 'Activo', 'applies_to' => 'Todos'], // mixed case
            ['status' => 'FINALIZADO', 'applies_to' => 'ESTUDIANTES'],
            ['status' => 'finalizado', 'applies_to' => 'estudiantes'],
            ['status' => 'ELIMINADO', 'applies_to' => 'CARRERA'],
            ['status' => 'eliminado', 'applies_to' => 'carrera'],
            ['status' => 'DESACTIVADO', 'applies_to' => 'SEMESTRE'],
            ['status' => 'desactivado', 'applies_to' => 'semestre'],
        ];

        foreach ($testCases as $case) {
            $data = [
                'concept_name' => 'Test',
                'amount' => 100,
                'status' => $case['status'],
                'applies_to' => $case['applies_to'],
                'description' => null,
                'semestres' => [],
                'careers' => [],
                'students' => [],
                'exceptionStudents' => [],
                'applicantTags' => [],
            ];

            $result = PaymentConceptMapper::toCreateConceptDTO($data);

            // Should work with any case (strtolower is applied)
            $this->assertInstanceOf(CreatePaymentConceptDTO::class, $result);
            $this->assertEquals(strtolower($case['status']), $result->status->value);
            $this->assertEquals(strtolower($case['applies_to']), $result->appliesTo->value);
        }
    }

    #[Test]
    public function to_create_concept_dto_amount_formatting(): void
    {
        $testCases = [
            ['input' => 100, 'expected' => '100.00'],
            ['input' => 100.0, 'expected' => '100.00'],
            ['input' => 100.00, 'expected' => '100.00'],
            ['input' => 100.5, 'expected' => '100.50'],
            ['input' => 100.55, 'expected' => '100.55'],
            ['input' => 100.555, 'expected' => '100.56'], // Rounded
            ['input' => 0, 'expected' => '0.00'],
            ['input' => 999999.99, 'expected' => '999999.99'],
        ];

        foreach ($testCases as $case) {
            $data = [
                'concept_name' => 'Test',
                'amount' => $case['input'],
                'status' => 'activo',
                'applies_to' => 'todos',
                'description' => null,
                'semestres' => [],
                'careers' => [],
                'students' => [],
                'exceptionStudents' => [],
                'applicantTags' => [],
            ];

            $result = PaymentConceptMapper::toCreateConceptDTO($data);
            $this->assertEquals($case['expected'], $result->amount,
                "Failed for amount: {$case['input']}");
        }
    }

    // ==================== TO UPDATE CONCEPT DTO TESTS ====================

    #[Test]
    public function to_update_concept_dto_creates_dto_from_array(): void
    {
        // Arrange
        $data = [
            'id' => 123,
            'concept_name' => 'Updated Concept',
            'description' => 'Updated description',
            'start_date' => '2024-03-01',
            'end_date' => '2024-07-31',
            'amount' => 300.75,
        ];

        // Act
        $result = PaymentConceptMapper::toUpdateConceptDTO($data);

        // Assert
        $this->assertInstanceOf(UpdatePaymentConceptDTO::class, $result);
        $this->assertEquals(123, $result->id);
        $this->assertEquals('Updated Concept', $result->concept_name);
        $this->assertEquals('Updated description', $result->description);
        $this->assertEquals('2024-03-01', $result->start_date->toDateString());
        $this->assertEquals('2024-07-31', $result->end_date->toDateString());
        $this->assertEquals('300.75', $result->amount); // Formatted
    }

    #[Test]
    public function to_update_concept_dto_with_null_dates(): void
    {
        // Arrange
        $data = [
            'id' => 123,
            'concept_name' => 'Test',
            'description' => 'Test',
            'start_date' => null,
            'end_date' => null,
            'amount' => 100,
        ];

        // Act
        $result = PaymentConceptMapper::toUpdateConceptDTO($data);

        // Assert
        $this->assertInstanceOf(UpdatePaymentConceptDTO::class, $result);
        $this->assertNull($result->start_date); // Should be null
        $this->assertNull($result->end_date); // Should be null
        $this->assertEquals('100.00', $result->amount);
    }

    // ==================== TO UPDATE CONCEPT RELATIONS DTO TESTS ====================

    #[Test]
    public function to_update_concept_relations_dto_creates_dto_from_array(): void
    {
        // Arrange
        $data = [
            'id' => 456,
            'semesters' => ['2024-1', '2024-2'],
            'careers' => [1, 2, 3],
            'students' => [101, 102, 103],
            'applies_to' => 'carrera', // lowercase
            'replaceRelations' => true,
            'exceptionStudents' => [201, 202],
            'replaceExceptions' => false,
            'removeAllExceptions' => false,
            'applicantTags' => ['applicant', 'no_student_details'], // lowercase enum values
        ];

        // Act
        $result = PaymentConceptMapper::toUpdateConceptRelationsDTO($data);

        // Assert
        $this->assertInstanceOf(UpdatePaymentConceptRelationsDTO::class, $result);
        $this->assertEquals(456, $result->id);
        $this->assertEquals(['2024-1', '2024-2'], $result->semesters);
        $this->assertEquals([1, 2, 3], $result->careers);
        $this->assertEquals([101, 102, 103], $result->students);
        $this->assertEquals(PaymentConceptAppliesTo::CARRERA, $result->appliesTo);
        $this->assertTrue($result->replaceRelations);
        $this->assertEquals([201, 202], $result->exceptionStudents);
        $this->assertFalse($result->replaceExceptions);
        $this->assertFalse($result->removeAllExceptions);

        // El mapper usa PaymentConceptAppliesTo en lugar de PaymentConceptApplicantType
        $this->assertEquals([PaymentConceptApplicantType::APPLICANT, PaymentConceptApplicantType::NO_STUDENT_DETAILS], $result->applicantTags);
    }

    #[Test]
    public function to_update_concept_relations_dto_with_null_applies_to(): void
    {
        // Arrange
        $data = [
            'id' => 456,
            'semesters' => [],
            'careers' => [],
            'students' => [],
            // applies_to missing
            'replaceRelations' => false,
            'exceptionStudents' => [],
            'replaceExceptions' => false,
            'removeAllExceptions' => false,
            'applicantTags' => [],
        ];

        // Act
        $result = PaymentConceptMapper::toUpdateConceptRelationsDTO($data);

        // Assert
        $this->assertInstanceOf(UpdatePaymentConceptRelationsDTO::class, $result);
        $this->assertEquals(456, $result->id);
        $this->assertNull($result->appliesTo); // Should be null when not provided
    }

    // ==================== TO PENDING PAYMENT CONCEPT RESPONSE TESTS ====================

    #[Test]
    public function to_pending_payment_concept_response_creates_response(): void
    {
        // Arrange
        $pc = [
            'id' => 789,
            'concept_name' => 'Pending Concept',
            'description' => 'Pending description',
            'amount' => '150.00',
            'start_date' => '2024-01-15 10:30:00',
            'end_date' => '2024-06-30 23:59:59',
        ];

        // Act
        $result = PaymentConceptMapper::toPendingPaymentConceptResponse($pc);

        // Assert
        $this->assertInstanceOf(PendingPaymentConceptsResponse::class, $result);
        $this->assertEquals(789, $result->id);
        $this->assertEquals('Pending Concept', $result->concept_name);
        $this->assertEquals('Pending description', $result->description);
        $this->assertEquals('150.00', $result->amount);
        $this->assertEquals('2024-01-15 10:30:00', $result->start_date);
        $this->assertEquals('2024-06-30 23:59:59', $result->end_date);
    }

    #[Test]
    public function to_pending_payment_concept_response_with_null_end_date(): void
    {
        // Arrange
        $pc = [
            'id' => 789,
            'concept_name' => 'No End Date',
            'description' => null,
            'amount' => '100.00',
            'start_date' => '2024-01-01 00:00:00',
            'end_date' => null,
        ];

        // Act
        $result = PaymentConceptMapper::toPendingPaymentConceptResponse($pc);

        // Assert
        $this->assertInstanceOf(PendingPaymentConceptsResponse::class, $result);
        $this->assertEquals(789, $result->id);
        $this->assertEquals('No End Date', $result->concept_name);
        $this->assertNull($result->description);
        $this->assertEquals('100.00', $result->amount);
        $this->assertEquals('2024-01-01 00:00:00', $result->start_date);
        $this->assertNull($result->end_date);
    }

    // ==================== TO CONCEPTS TO DASHBOARD RESPONSE TESTS ====================

    #[Test]
    public function to_concepts_to_dashboard_response_creates_response(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'concept_name' => 'Dashboard Concept',
            'status' => PaymentConceptStatus::ACTIVO,
            'amount' => '250.75',
            'applies_to' => PaymentConceptAppliesTo::TODOS,
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
        ]);

        // Act
        $result = PaymentConceptMapper::toConceptsToDashboardResponse($concept);

        // Assert
        $this->assertInstanceOf(ConceptsToDashboardResponse::class, $result);
        $this->assertEquals($concept->id, $result->id);
        $this->assertEquals('Dashboard Concept', $result->concept_name);
        $this->assertEquals('activo', $result->status); // lowercase
        $this->assertEquals('250.75', $result->amount);
        $this->assertEquals('todos', $result->applies_to); // lowercase
        $this->assertEquals('2024-01-01 00:00:00', $result->start_date);
        $this->assertEquals('2024-12-31 00:00:00', $result->end_date);
    }

    // ==================== TO PENDING PAYMENT SUMMARY TESTS ====================

    #[Test]
    public function to_pending_payment_summary_creates_response(): void
    {
        // Arrange
        $data = [
            'total_amount' => '1250.50',
            'total_count' => 15,
        ];

        // Act
        $result = PaymentConceptMapper::toPendingPaymentSummary($data);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        $this->assertEquals('1250.50', $result->totalAmount);
        $this->assertEquals(15, $result->totalCount);
    }

    // ==================== TO CONCEPT NAME AND AMOUNT RESPONSE TESTS ====================

    #[Test]
    public function to_concept_name_and_amount_response_creates_response(): void
    {
        // Arrange
        $data = [
            'user_name' => 'John Doe',
            'concept_name' => 'Tuition Fee',
            'amount' => '500.00',
        ];

        // Act
        $result = PaymentConceptMapper::toConceptNameAndAmoutResonse($data);

        // Assert
        $this->assertInstanceOf(ConceptNameAndAmountResponse::class, $result);
        $this->assertEquals('John Doe', $result->user_name);
        $this->assertEquals('Tuition Fee', $result->concept_name);
        $this->assertEquals('500.00', $result->amount);
    }

    // ==================== TO CREATE PAYMENT CONCEPT RESPONSE TESTS ====================

    #[Test]
    public function to_create_payment_concept_response_creates_response(): void
    {
        // Arrange
        Carbon::setTestNow('2024-01-15 14:30:00');

        // Necesitamos simular la entidad con el constructor correcto
        $paymentConcept = new class extends EntitiesPaymentConcept {
            public function __construct()
            {
                // Usar reflexión para construir el objeto
                $reflection = new ReflectionClass(EntitiesPaymentConcept::class);
                $constructor = $reflection->getConstructor();

                // Valores para el constructor
                $params = [
                    'concept_name' => 'New Concept',
                    'status' => PaymentConceptStatus::ACTIVO,
                    'start_date' => Carbon::parse('2024-02-01'),
                    'amount' => '300.00',
                    'applies_to' => PaymentConceptAppliesTo::CARRERA,
                    'userIds' => [1, 2, 3],
                    'careerIds' => [10, 20],
                    'semesters' => ['2024-1'],
                    'exceptionUserIds' => [100],
                    'applicantTags' => [PaymentConceptApplicantType::APPLICANT],
                    'id' => 123,
                    'description' => 'Test description',
                    'end_date' => Carbon::parse('2024-06-30')
                ];

                // Crear instancia
                $instance = $reflection->newInstanceWithoutConstructor();

                // Establecer propiedades
                foreach ($params as $key => $value) {
                    $property = $reflection->getProperty($key);
                    $property->setAccessible(true);
                    $property->setValue($instance, $value);
                }

                // Reemplazar $this
                $this->concept_name = $instance->concept_name;
                $this->status = $instance->status;
                $this->start_date = $instance->start_date;
                $this->amount = $instance->amount;
                $this->applies_to = $instance->applies_to;
                $this->id = $instance->id;
                $this->description = $instance->description;
                $this->end_date = $instance->end_date;
            }

            // Mock de los métodos getter
            public function getExceptionUsersIds(): array { return [100]; }
            public function getCareerIds(): array { return [10, 20]; }
            public function getSemesters(): array { return ['2024-1']; }
        };

        $affectedCount = 50;

        // Act
        $result = PaymentConceptMapper::toCreatePaymentConceptResponse($paymentConcept, $affectedCount);

        // Assert
        $this->assertInstanceOf(CreatePaymentConceptResponse::class, $result);
        $this->assertEquals(123, $result->id);
        $this->assertEquals('New Concept', $result->conceptName);
        $this->assertEquals('activo', $result->status); // lowercase
        $this->assertEquals('carrera', $result->appliesTo); // lowercase
        $this->assertEquals('300.00', $result->amount);
        $this->assertEquals('2024-02-01', $result->startDate);
        $this->assertEquals('2024-06-30', $result->endDate);
        $this->assertEquals(50, $result->affectedStudentsCount);
        $this->assertEquals(
            'Concepto creado exitosamente. Afecta a 50 estudiante(s)',
            $result->message
        );
        $this->assertEquals('2024-01-15 14:30:00', $result->createdAt);
        $this->assertEquals('Test description', $result->description);

        // Check metadata
        $this->assertArrayHasKey('exception_count', $result->metadata);
        $this->assertArrayHasKey('career_count', $result->metadata);
        $this->assertArrayHasKey('semester_count', $result->metadata);
        $this->assertEquals(1, $result->metadata['exception_count']);
        $this->assertEquals(2, $result->metadata['career_count']);
        $this->assertEquals(1, $result->metadata['semester_count']);
    }

    // ==================== TO UPDATE PAYMENT CONCEPT RESPONSE TESTS ====================

    #[Test]
    public function to_update_payment_concept_response_creates_response(): void
    {
        // Arrange
        Carbon::setTestNow('2024-01-20 10:00:00');

        // Mock de la entidad
        $paymentConcept = new class extends EntitiesPaymentConcept {
            public function __construct()
            {
                $reflection = new ReflectionClass(EntitiesPaymentConcept::class);

                $params = [
                    'concept_name' => 'Updated Concept',
                    'status' => PaymentConceptStatus::ACTIVO,
                    'start_date' => Carbon::parse('2024-03-01'),
                    'amount' => '350.00',
                    'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES,
                    'id' => 456,
                    'description' => 'Updated description',
                    'end_date' => Carbon::parse('2024-08-31')
                ];

                $instance = $reflection->newInstanceWithoutConstructor();

                foreach ($params as $key => $value) {
                    $property = $reflection->getProperty($key);
                    $property->setAccessible(true);
                    $property->setValue($instance, $value);
                }

                $this->concept_name = $instance->concept_name;
                $this->status = $instance->status;
                $this->start_date = $instance->start_date;
                $this->amount = $instance->amount;
                $this->applies_to = $instance->applies_to;
                $this->id = $instance->id;
                $this->description = $instance->description;
                $this->end_date = $instance->end_date;
            }
        };

        $data = [
            'message' => 'Concepto actualizado exitosamente',
            'changes' => [
                'amount' => ['from' => '300.00', 'to' => '350.00'],
                'end_date' => ['from' => '2024-06-30', 'to' => '2024-08-31'],
            ],
        ];

        // Act
        $result = PaymentConceptMapper::toUpdatePaymentConceptResponse($paymentConcept, $data);

        // Assert
        $this->assertInstanceOf(UpdatePaymentConceptResponse::class, $result);
        $this->assertEquals(456, $result->id);
        $this->assertEquals('Updated Concept', $result->conceptName);
        $this->assertEquals('activo', $result->status); // lowercase
        $this->assertEquals('estudiantes', $result->appliesTo); // lowercase
        $this->assertEquals('Updated description', $result->description);
        $this->assertEquals('350.00', $result->amount);
        $this->assertEquals('2024-03-01', $result->startDate);
        $this->assertEquals('2024-08-31', $result->endDate);
        $this->assertEquals('Concepto actualizado exitosamente', $result->message);
        $this->assertEquals('2024-01-20 10:00:00', $result->updatedAt);
        $this->assertEquals($data['changes'], $result->changes);
    }

    // ==================== TO UPDATE PAYMENT CONCEPT RELATIONS RESPONSE TESTS ====================

    #[Test]
    public function to_update_payment_concept_relations_response_creates_response(): void
    {
        // Arrange
        Carbon::setTestNow('2024-01-25 15:45:00');

        // Mock de la entidad
        $paymentConcept = new class extends EntitiesPaymentConcept {
            public function __construct()
            {
                $reflection = new \ReflectionClass(EntitiesPaymentConcept::class);

                $params = [
                    'concept_name' => 'Relations Updated',
                    'status' => PaymentConceptStatus::ACTIVO,
                    'start_date' => Carbon::parse('2024-01-01'),
                    'amount' => '200.00',
                    'applies_to' => PaymentConceptAppliesTo::CARRERA,
                    'id' => 789,
                    'description' => null,
                    'end_date' => null
                ];

                $instance = $reflection->newInstanceWithoutConstructor();

                foreach ($params as $key => $value) {
                    $property = $reflection->getProperty($key);
                    $property->setAccessible(true);
                    $property->setValue($instance, $value);
                }

                $this->concept_name = $instance->concept_name;
                $this->status = $instance->status;
                $this->start_date = $instance->start_date;
                $this->amount = $instance->amount;
                $this->applies_to = $instance->applies_to;
                $this->id = $instance->id;
                $this->description = $instance->description;
                $this->end_date = $instance->end_date;
            }

            // Mock de los métodos getter
            public function getUserIds(): array { return [1, 2, 3, 4, 5]; }
            public function getExceptionUsersIds(): array { return [100, 101]; }
            public function getCareerIds(): array { return [10, 20]; }
            public function getSemesters(): array { return ['2024-1', '2024-2']; }
            public function getApplicantTag(): array { return [PaymentConceptApplicantType::NO_STUDENT_DETAILS]; }
        };

        $data = [
            'message' => 'Relaciones actualizadas',
            'changes' => ['applies_to' => ['from' => 'todos', 'to' => 'carrera']], // lowercase
            'affectedSummary' => ['added' => 5, 'removed' => 2],
        ];

        // Act
        $result = PaymentConceptMapper::toUpdatePaymentConceptRelationsResponse($paymentConcept, $data);

        // Assert
        $this->assertInstanceOf(UpdatePaymentConceptRelationsResponse::class, $result);
        $this->assertEquals('activo', $result->status); // lowercase
        $this->assertEquals('Relaciones actualizadas', $result->message);
        $this->assertEquals('2024-01-25 15:45:00', $result->updatedAt);
        $this->assertEquals($data['changes'], $result->changes);
        $this->assertEquals($data['affectedSummary'], $result->affectedSummary);

        // Check metadata
        $this->assertArrayHasKey('concept_name', $result->metadata);
        $this->assertArrayHasKey('applies_to', $result->metadata);
        $this->assertArrayHasKey('students_count', $result->metadata);
        $this->assertArrayHasKey('exception_count', $result->metadata);
        $this->assertArrayHasKey('career_count', $result->metadata);
        $this->assertArrayHasKey('semester_count', $result->metadata);
        $this->assertArrayHasKey('tags', $result->metadata);
    }

    // ==================== TO CONCEPT CHANGE STATUS RESPONSE TESTS ====================

    #[Test]
    public function to_concept_change_status_response_creates_response(): void
    {
        // Arrange
        Carbon::setTestNow('2024-02-01 09:00:00');

        // Mock de la entidad
        $paymentConcept = new class extends EntitiesPaymentConcept {
            public function __construct()
            {
                $reflection = new ReflectionClass(EntitiesPaymentConcept::class);

                $params = [
                    'concept_name' => 'Status Changed',
                    'status' => PaymentConceptStatus::FINALIZADO,
                    'start_date' => Carbon::parse('2024-01-01'),
                    'amount' => '150.00',
                    'applies_to' => PaymentConceptAppliesTo::TODOS,
                    'id' => 999,
                    'description' => 'Test concept',
                    'end_date' => Carbon::parse('2024-12-31')
                ];

                $instance = $reflection->newInstanceWithoutConstructor();

                foreach ($params as $key => $value) {
                    $property = $reflection->getProperty($key);
                    $property->setAccessible(true);
                    $property->setValue($instance, $value);
                }

                $this->concept_name = $instance->concept_name;
                $this->status = $instance->status;
                $this->start_date = $instance->start_date;
                $this->amount = $instance->amount;
                $this->applies_to = $instance->applies_to;
                $this->id = $instance->id;
                $this->description = $instance->description;
                $this->end_date = $instance->end_date;
            }
        };

        $data = [
            'message' => 'Estado cambiado exitosamente',
            'changes' => ['status' => ['from' => 'activo', 'to' => 'finalizado']], // lowercase
        ];

        // Act
        $result = PaymentConceptMapper::toConceptChangeStatusResponse($paymentConcept, $data);

        // Assert
        $this->assertInstanceOf(ConceptChangeStatusResponse::class, $result);
        $this->assertArrayHasKey('id', $result->conceptData);
        $this->assertEquals(999, $result->conceptData['id']);
        $this->assertEquals('Status Changed', $result->conceptData['concept_name']);
        $this->assertEquals('finalizado', $result->conceptData['status']); // lowercase
        $this->assertEquals('150.00', $result->conceptData['amount']);
        $this->assertEquals('2024-01-01', $result->conceptData['start_date']);
        $this->assertEquals('2024-12-31', $result->conceptData['end_date']);
        $this->assertEquals('todos', $result->conceptData['applies_to']); // lowercase
        $this->assertEquals('Estado cambiado exitosamente', $result->message);
        $this->assertEquals($data['changes'], $result->changes);
        $this->assertEquals('2024-02-01 09:00:00', $result->updatedAt);
    }
}
