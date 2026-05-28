<?php

namespace Tests\Unit\Infraestructure\Repositories\Query;

use App\Core\Application\DTO\Response\PaymentConcept\ConceptToDisplay;
use App\Core\Application\DTO\Response\PaymentConcept\PendingSummaryResponse;
use App\Core\Domain\Entities\PaymentConcept as DomainPaymentConcept;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Core\Infraestructure\Repositories\Query\Payments\EloquentPaymentConceptQueryRepository;
use App\Models\Career;
use App\Models\PaymentConcept as EloquentPaymentConcept;
use App\Models\StudentDetail;
use App\Models\User as EloquentUser;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class EloquentPaymentConceptQueryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentPaymentConceptQueryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles necesarios
        \Spatie\Permission\Models\Role::create(['name' => UserRoles::STUDENT->value, 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => UserRoles::ADMIN->value, 'guard_name' => 'web']);

        $this->repository = new EloquentPaymentConceptQueryRepository();
    }

    // ==================== BASIC TESTS ====================

    #[Test]
    public function find_by_id_successfully(): void
    {
        // Arrange
        $paymentConcept = EloquentPaymentConcept::factory()->active()->create();

        // Act
        $result = $this->repository->findById($paymentConcept->id);

        // Assert
        $this->assertInstanceOf(DomainPaymentConcept::class, $result);
        $this->assertEquals($paymentConcept->id, $result->id);
        $this->assertEquals($paymentConcept->concept_name, $result->concept_name);
    }

    #[Test]
    public function find_by_id_returns_null_for_nonexistent_id(): void
    {
        // Act
        $result = $this->repository->findById(999999);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function find_by_id_with_all_relations(): void
    {
        // Arrange
        $paymentConcept = EloquentPaymentConcept::factory()->active()->create();

        // Create and attach relations
        $career = Career::factory()->create();
        $student = EloquentUser::factory()->create();
        $student->assignRole(UserRoles::STUDENT->value);

        $paymentConcept->careers()->attach($career->id);
        $paymentConcept->users()->attach($student->id);
        $paymentConcept->paymentConceptSemesters()->create(['semestre' => 5]);

        // Act
        $result = $this->repository->findById($paymentConcept->id);

        // Assert
        $this->assertInstanceOf(DomainPaymentConcept::class, $result);
        $this->assertNotNull($result);
    }

    // ==================== PENDING PAYMENT CONCEPTS TESTS ====================

    #[Test]
    public function get_pending_payment_concepts_for_student(): void
    {
        // Arrange
        $student = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com'
        ]);
        $student->assignRole(UserRoles::STUDENT->value);

        $career = Career::factory()->create();
        StudentDetail::factory()->forUser($student)->forCareer($career)->create([
            'semestre' => 5,
            'n_control' => '20230001'
        ]);

        $domainUser = UserMapper::toDomain($student->load('studentDetail'));

        // Create active payment concept with applies_to = 'todos'
        $paymentConcept = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES->value,
            'amount' => '1500.00',
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->addDays(30)
        ]);

        // Attach the student to the concept (required for 'todos' to work)
        $paymentConcept->users()->attach($student->id);

        // Act
        $result = $this->repository->getPendingPaymentConcepts($domainUser, false);
        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        $this->assertEquals('1500.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);
    }

    #[Test]
    public function get_pending_payment_concepts_excludes_paid_concepts(): void
    {
        // Arrange
        $student = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com'
        ]);
        $student->assignRole(UserRoles::STUDENT->value);

        $career = Career::factory()->create();
        StudentDetail::factory()->forUser($student)->forCareer($career)->create();
        $domainUser = UserMapper::toDomain($student->load('studentDetail'));

        $paymentConcept = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES->value,
            'amount' => '2000.00'
        ]);

        // Attach the student to the concept
        $paymentConcept->users()->attach($student->id);

        // Create paid payment
        Payment::factory()->create([
            'user_id' => $student->id,
            'payment_concept_id' => $paymentConcept->id,
            'status' => PaymentStatus::PAID->value,
            'amount' => '2000.00',
            'amount_received' => '2000.00'
        ]);

        // Act
        $result = $this->repository->getPendingPaymentConcepts($domainUser, false);

        // Assert
        $this->assertEquals('0.00', $result->totalAmount);
        $this->assertEquals(0, $result->totalCount);
    }

    #[Test]
    public function get_pending_payment_concepts_with_details(): void
    {
        // Arrange
        $student = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'name' => 'Alice',
            'last_name' => 'Johnson',
            'email' => 'alice.johnson@example.com'
        ]);
        $student->assignRole(UserRoles::STUDENT->value);

        $career = Career::factory()->create();
        StudentDetail::factory()->forUser($student)->forCareer($career)->create();
        $domainUser = UserMapper::toDomain($student->load('studentDetail'));

        // Create multiple concepts
        $concept1 = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'concept_name' => 'Tuition Fee',
            'amount' => '3000.00',
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20)
        ]);

        $concept2 = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'concept_name' => 'Library Fee',
            'amount' => '500.00',
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->addDays(25)
        ]);

        // Act
        $result = $this->repository->getPendingPaymentConceptsWithDetails($domainUser);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        // Assert
        $conceptNames = array_map(fn($item) => $item->concept_name, $result);
        $this->assertContains('Tuition Fee', $conceptNames);
        $this->assertContains('Library Fee', $conceptNames);
    }

    // ==================== OVERDUE PAYMENTS TESTS ====================

    #[Test]
    public function get_overdue_payments_summary(): void
    {
        // Arrange
        $student = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'name' => 'Bob',
            'last_name' => 'Wilson',
            'email' => 'bob.wilson@example.com'
        ]);
        $student->assignRole(UserRoles::STUDENT->value);

        $career = Career::factory()->create();
        StudentDetail::factory()->forUser($student)->forCareer($career)->create();
        $domainUser = UserMapper::toDomain($student->load('studentDetail'));

        // Create expired payment concept with status FINALIZADO
        $paymentConcept = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::FINALIZADO->value,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'amount' => '2500.00',
            'start_date' => Carbon::now()->subDays(60),
            'end_date' => Carbon::now()->subDays(1) // Already expired
        ]);


        // Act
        $result = $this->repository->getOverduePaymentsSummary($domainUser, false);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        $this->assertEquals('2500.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);
    }

    // ==================== FIND ALL CONCEPTS TESTS ====================

    #[Test]
    public function find_all_concepts_with_pagination(): void
    {
        // Arrange
        EloquentPaymentConcept::factory()->count(15)->create([
            'status' => PaymentConceptStatus::ACTIVO->value
        ]);

        // Act
        $result = $this->repository->findAllConcepts('todos', 10, 1);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(15, $result->total());
        $this->assertEquals(10, $result->perPage());
        $this->assertCount(10, $result->items());
    }

    #[Test]
    public function find_all_concepts_filtered_by_status(): void
    {
        // Arrange
        EloquentPaymentConcept::factory()->count(3)->create([
            'status' => PaymentConceptStatus::ACTIVO->value
        ]);

        EloquentPaymentConcept::factory()->count(2)->create([
            'status' => PaymentConceptStatus::DESACTIVADO->value
        ]);

        // Act
        $result = $this->repository->findAllConcepts(PaymentConceptStatus::ACTIVO->value, 10, 1);

        // Assert
        $this->assertEquals(3, $result->total());
        foreach ($result->items() as $item) {
            // Convertir a string si es necesario
            $statusValue = is_string($item->status) ? $item->status : $item->status->value;
            $this->assertEquals(PaymentConceptStatus::ACTIVO->value, $statusValue);
        }
    }

    // ==================== DASHBOARD CONCEPTS TESTS ====================

    #[Test]
    public function get_concepts_to_dashboard_with_pagination(): void
    {
        // Arrange
        EloquentPaymentConcept::factory()->count(8)->create([
            'status' => PaymentConceptStatus::ACTIVO->value
        ]);

        // Act
        $result = $this->repository->getConceptsToDashboard(false, 5, 1);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(8, $result->total());
        $this->assertEquals(5, $result->perPage());
        $this->assertCount(5, $result->items());
    }

    // ==================== FILTERING TESTS ====================

    #[Test]
    public function payment_concepts_filter_by_career(): void
    {
        // Arrange
        $student = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'name' => 'Charlie',
            'last_name' => 'Brown',
            'email' => 'charlie.brown@example.com'
        ]);
        $student->assignRole(UserRoles::STUDENT->value);

        $career = Career::factory()->create();
        StudentDetail::factory()->forUser($student)->forCareer($career)->create();

        $domainUser = UserMapper::toDomain($student->load('studentDetail'));

        // Create concept for specific career
        $paymentConcept = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::CARRERA->value,
            'amount' => '1800.00',
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20)
        ]);

        $paymentConcept->careers()->attach($career->id);

        // Act
        $result = $this->repository->getPendingPaymentConcepts($domainUser, false);

        // Assert
        $this->assertEquals('1800.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);
    }

    #[Test]
    public function payment_concepts_filter_by_semester(): void
    {
        // Arrange
        $student = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'name' => 'David',
            'last_name' => 'Miller',
            'email' => 'david.miller@example.com'
        ]);
        $student->assignRole(UserRoles::STUDENT->value);

        $career = Career::factory()->create();
        StudentDetail::factory()->forUser($student)->forCareer($career)->create(['semestre' => 5]);

        $domainUser = UserMapper::toDomain($student->load('studentDetail'));

        // Create concept for specific semester
        $paymentConcept = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'amount' => '1200.00',
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20)
        ]);

        $paymentConcept->paymentConceptSemesters()->create(['semestre' => 5]);
        $paymentConcept->users()->attach($student->id); // Required for 'todos'

        // Act
        $result = $this->repository->getPendingPaymentConcepts($domainUser, false);

        // Assert
        $this->assertEquals('1200.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);
    }

    #[Test]
    public function payment_concepts_with_exceptions(): void
    {
        // Arrange
        $student = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'name' => 'Emma',
            'last_name' => 'Davis',
            'email' => 'emma.davis@example.com'
        ]);
        $student->assignRole(UserRoles::STUDENT->value);

        $career = Career::factory()->create();
        StudentDetail::factory()->forUser($student)->forCareer($career)->create();
        $domainUser = UserMapper::toDomain($student->load('studentDetail'));

        // Create concept with exception for this student
        $paymentConcept = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'amount' => '1500.00'
        ]);

        // Create exception for the test student
        $paymentConcept->exceptions()->attach(['user_id' => $student->id]);

        // Act
        $result = $this->repository->getPendingPaymentConcepts($domainUser, false);

        // Assert
        $this->assertEquals('0.00', $result->totalAmount);
        $this->assertEquals(0, $result->totalCount);
    }

    // ==================== APPLICANT TESTS ====================

    #[Test]
    public function student_without_details_handled_correctly(): void
    {
        // Arrange
        $student = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'name' => 'Frank',
            'last_name' => 'Taylor',
            'email' => 'frank.taylor@example.com'
        ]);
        $student->assignRole(UserRoles::STUDENT->value);
        $student->load('roles');

        $domainUser = UserMapper::toDomain($student);

        // Create concept for applicants
        $paymentConcept = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::TAG->value,
            'amount' => '1000.00',
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20)
        ]);


        $paymentConcept->applicantTypes()->create([
            'tag' => PaymentConceptApplicantType::NO_STUDENT_DETAILS->value
        ]);

        // Act
        $result = $this->repository->getPendingPaymentConcepts($domainUser, false);

        // Assert
        $this->assertEquals('1000.00', $result->totalAmount,
            "Total amount no coincide. Esperado: 1000.00, Obtenido: " . ($result->totalAmount ?? 'N/A'));

        $this->assertEquals(1, $result->totalCount,
            "Total count no coincide. Esperado: 1, Obtenido: " . ($result->totalCount ?? 'N/A'));

    }

    // ==================== INTEGRATION TEST ====================

    #[Test]
    public function complete_payment_concept_workflow(): void
    {
        // 1. Create student
        $student = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'name' => 'Grace',
            'last_name' => 'Anderson',
            'email' => 'grace.anderson@example.com'
        ]);
        $student->assignRole(UserRoles::STUDENT->value);

        $career = Career::factory()->create();
        StudentDetail::factory()->forUser($student)->forCareer($career)->create();

        $domainUser = UserMapper::toDomain($student->load('studentDetail'));

        // 2. Create payment concept
        $paymentConcept = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'concept_name' => 'Integration Test Concept',
            'amount' => '3500.00',
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20)
        ]);

        // Attach student to the concept
        $paymentConcept->users()->attach($student->id);

        // 3. Test find by ID
        $foundConcept = $this->repository->findById($paymentConcept->id);
        $this->assertInstanceOf(DomainPaymentConcept::class, $foundConcept);
        $this->assertEquals('Integration Test Concept', $foundConcept->concept_name);

        // 4. Test pending concepts
        $pending = $this->repository->getPendingPaymentConcepts($domainUser, false);
        $this->assertEquals('3500.00', $pending->totalAmount);

        // 5. Test pending details
        $pendingDetails = $this->repository->getPendingPaymentConceptsWithDetails($domainUser);
        $this->assertCount(1, $pendingDetails);

        // 6. Test find all concepts
        $allConcepts = $this->repository->findAllConcepts('todos', 10, 1);
        $this->assertGreaterThanOrEqual(1, $allConcepts->total());

        // 7. Test dashboard concepts
        $dashboardConcepts = $this->repository->getConceptsToDashboard(false, 10, 1);
        $this->assertGreaterThanOrEqual(1, $dashboardConcepts->total());

        // 8. Mark concept as FINALIZADO and test overdue
        $paymentConcept->update([
            'status' => PaymentConceptStatus::FINALIZADO->value,
            'end_date' => Carbon::now()->subDays(1)
        ]);

        $overdue = $this->repository->getOverduePaymentsSummary($domainUser, false);
        $this->assertEquals('3500.00', $overdue->totalAmount);
    }

    #[Test]
    public function get_all_pending_payment_amount(): void
    {
        $paymentConcept = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'amount' => '1000.00',
            'start_date' => now()->subDays(10),
            'end_date' => now()->addDays(10),
        ]);
        $students = EloquentUser::factory()->count(40)->create();
        $career1 = Career::factory()->create();
        $count = 0;
        foreach ($students as $index => $student)
        {
            $student->assignRole(UserRoles::STUDENT->value);
            StudentDetail::factory()->forUser($student)->forCareer($career1)->create();
            $count++;

            if($count >30 && $count< 35)
            {
                Payment::factory()->create([
                    'user_id' => $student->id,
                    'payment_concept_id' => $paymentConcept->id,
                    'status' => PaymentStatus::UNDERPAID->value,
                    'amount' => '1000.00',
                    'amount_received' => '100.00',
                    'stripe_session_id' => 'cs_test_' . uniqid() . '_' . $index,
                ]);

            }

            if($count >35 && $count< 38)
            {
                Payment::factory()->create([
                    'user_id' => $student->id,
                    'payment_concept_id' => $paymentConcept->id,
                    'status' => PaymentStatus::UNDERPAID->value,
                    'amount' => '1000.00',
                    'amount_received' => '800.00',
                    'stripe_session_id' => 'cs_test_' . uniqid() . '_' . $index,
                ]);

            }

            if($count > 38)
            {
                Payment::factory()->create([
                    'user_id' => $student->id,
                    'payment_concept_id' => $paymentConcept->id,
                    'status' => PaymentStatus::UNDERPAID->value,
                    'amount' => '1000.00',
                    'amount_received' => '200.00',
                    'stripe_session_id' => 'cs_test_' . uniqid() . '_' . $index,
                ]);
            }

        }
        $result = $this->repository->getAllPendingPaymentAmount(false);

        $this->assertEquals('37600.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);

    }


    #[Test]
    public function get_pending_with_details_for_students(): void
    {
        // Arrange
        $student1 = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com'
        ]);
        $student1->assignRole(UserRoles::STUDENT->value);

        $student2 = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com'
        ]);
        $student2->assignRole(UserRoles::STUDENT->value);

        $career = Career::factory()->create();

        // Student details
        StudentDetail::factory()->forUser($student1)->forCareer($career)->create([
            'semestre' => 5,
            'n_control' => '20230001'
        ]);

        StudentDetail::factory()->forUser($student2)->forCareer($career)->create([
            'semestre' => 3,
            'n_control' => '20230002'
        ]);

        // Create multiple payment concepts
        $concept1 = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'concept_name' => 'Tuition Fee',
            'amount' => '3000.00',
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20)
        ]);

        $concept2 = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'concept_name' => 'Library Fee',
            'amount' => '500.00',
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->addDays(25)
        ]);

        $concept3 = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'concept_name' => 'Lab Fee',
            'amount' => '800.00',
            'start_date' => Carbon::now()->subDays(3),
            'end_date' => Carbon::now()->addDays(15)
        ]);

        // Act - Get pending details for both students
        $result = $this->repository->getPendingWithDetailsForStudents([$student1->id, $student2->id]);

        // Assert
        $this->assertIsArray($result);

        // Should have 6 items (3 concepts × 2 students)
        $this->assertCount(6, $result);

        // Check structure of results
        foreach ($result as $item) {
            $this->assertIsObject($item);
            $this->assertObjectHasProperty('user_name', $item);
            $this->assertObjectHasProperty('concept_name', $item);
            $this->assertObjectHasProperty('amount', $item);

            // Verify concept names
            $this->assertContains($item->concept_name, ['Tuition Fee', 'Library Fee', 'Lab Fee']);

            // Verify user names
            $this->assertContains($item->user_name, ['John Doe', 'Jane Smith']);
        }

        // Verify amounts
        $amounts = array_map(fn($item) => $item->amount, $result);
        $expectedAmounts = ['3000.00', '500.00', '800.00'];

        foreach ($expectedAmounts as $expectedAmount) {
            $this->assertTrue(in_array($expectedAmount, $amounts),
                "Expected amount $expectedAmount not found in results");
        }
    }

    #[Test]
    public function get_pending_with_details_for_students_with_partial_payments(): void
    {
        // Arrange
        $student = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'name' => 'Alice',
            'last_name' => 'Johnson',
            'email' => 'alice.johnson@example.com'
        ]);
        $student->assignRole(UserRoles::STUDENT->value);

        $career = Career::factory()->create();
        StudentDetail::factory()->forUser($student)->forCareer($career)->create();

        $concept = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES->value,
            'concept_name' => 'Partial Payment Concept',
            'amount' => '2000.00',
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20)
        ]);

        // Attach student to concept
        $concept->users()->attach($student->id);

        // Create a partial payment
        Payment::factory()->create([
            'user_id' => $student->id,
            'payment_concept_id' => $concept->id,
            'status' => PaymentStatus::UNDERPAID->value,
            'amount' => '2000.00',
            'amount_received' => '1000.00',
            'stripe_session_id' => 'cs_test_' . uniqid()
        ]);

        // Act
        $result = $this->repository->getPendingWithDetailsForStudents([$student->id]);

        // Assert
        $this->assertCount(1, $result);

        $item = $result[0];
        $this->assertEquals('Alice Johnson', $item->user_name);
        $this->assertEquals('Partial Payment Concept', $item->concept_name);
        // Should show pending amount (2000 - 1000 = 1000)
        $this->assertEquals('1000.00', $item->amount);
    }

    #[Test]
    public function get_pending_with_details_for_students_excludes_paid_concepts(): void
    {
        // Arrange
        $student = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'name' => 'Bob',
            'last_name' => 'Wilson',
            'email' => 'bob.wilson@example.com'
        ]);
        $student->assignRole(UserRoles::STUDENT->value);

        $career = Career::factory()->create();
        StudentDetail::factory()->forUser($student)->forCareer($career)->create();

        // Paid concept
        $paidConcept = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES->value,
            'concept_name' => 'Paid Concept',
            'amount' => '1500.00',
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20)
        ]);
        $paidConcept->users()->attach($student->id);

        $pendingConcept = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES->value,
            'concept_name' => 'Pending Concept',
            'amount' => '2000.00',
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20)
        ]);
        $pendingConcept->users()->attach($student->id);

        Payment::factory()->create([
            'user_id' => $student->id,
            'payment_concept_id' => $paidConcept->id,
            'status' => PaymentStatus::PAID->value,
            'amount' => '1500.00',
            'amount_received' => '1500.00',
            'stripe_session_id' => 'cs_test_' . uniqid()
        ]);

        // Pending concept


        // Act
        $result = $this->repository->getPendingWithDetailsForStudents([$student->id]);

        echo "\n=== RESULT ===\n";
        echo "Count: " . count($result) . "\n";
        foreach ($result as $item) {
            echo "- " . $item->concept_name . " (" . $item->amount . ")\n";
        }


        // Assert
        $this->assertCount(1, $result, "Expected 1 pending concept, got " . count($result));
        $this->assertEquals('Pending Concept', $result[0]->concept_name);
        $this->assertEquals('2000.00', $result[0]->amount);
    }

    #[Test]
    public function get_pending_with_details_for_students_empty_array_when_no_pending(): void
    {
        // Arrange
        $student = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO
        ]);
        $student->assignRole(UserRoles::STUDENT->value);

        $career = Career::factory()->create();
        StudentDetail::factory()->forUser($student)->forCareer($career)->create();

        // Act - No payment concepts created
        $result = $this->repository->getPendingWithDetailsForStudents([$student->id]);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function get_pending_with_details_for_students_returns_empty_for_empty_input(): void
    {
        // Act
        $result = $this->repository->getPendingWithDetailsForStudents([]);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function get_pending_with_details_for_students_filters_by_concept_applies_to(): void
    {
        // Arrange
        $now = Carbon::now();
        $student = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'name' => 'Charlie',
            'last_name' => 'Brown',
            'email' => 'charlie.brown@example.com'
        ]);
        $student->assignRole(UserRoles::STUDENT->value);

        $career = Career::factory()->create();
        $studentDetail=StudentDetail::factory()->forUser($student)->forCareer($career)->create([
            'semestre' => 5
        ]);

        // Concept that should apply (same career)
        $applicableConcept = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::CARRERA->value,
            'concept_name' => 'Career Concept',
            'amount' => '1200.00',
            'start_date' => $now->copy()->subDays(20),
            'end_date' => $now->copy()->addDays(10),
        ]);
        $applicableConcept->careers()->attach($career->id);

        // Concept that should NOT apply (different career)
        $otherCareer = Career::factory()->create();
        $nonApplicableConcept = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::CARRERA->value,
            'concept_name' => 'Other Career Concept',
            'amount' => '1500.00',
            'start_date' => $now->copy()->subDays(20), // Antes de hoy
            'end_date' => $now->copy()->addDays(10),

        ]);
        $nonApplicableConcept->careers()->attach($otherCareer->id);

        // Act
        $result = $this->repository->getPendingWithDetailsForStudents([$student->id]);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Career Concept', $result[0]->concept_name);
        $this->assertEquals('1200.00', $result[0]->amount);
    }

    #[Test]
    public function get_pending_with_details_for_students_orders_by_created_at_desc(): void
    {
        // Arrange
        $student = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'name' => 'David',
            'last_name' => 'Miller',
            'email' => 'david.miller@example.com'
        ]);
        $student->assignRole(UserRoles::STUDENT->value);

        $career = Career::factory()->create();
        StudentDetail::factory()->forUser($student)->forCareer($career)->create();

        $now = Carbon::now();

        // Create concepts with different creation dates
        $olderConcept = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES->value,
            'concept_name' => 'Older Concept',
            'amount' => '1000.00',
            'created_at' => Carbon::now()->subDays(10),
            'start_date' => $now->copy()->subDays(20), // Antes de hoy
            'end_date' => $now->copy()->addDays(10),
        ]);

        $newerConcept = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES->value,
            'concept_name' => 'Newer Concept',
            'amount' => '2000.00',
            'created_at' => Carbon::now()->subDays(5),
            'start_date' => $now->copy()->subDays(20), // Antes de hoy
            'end_date' => $now->copy()->addDays(10),
        ]);

        $latestConcept = EloquentPaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES->value,
            'concept_name' => 'Latest Concept',
            'amount' => '3000.00',
            'created_at' => Carbon::now()->subDays(1),
            'start_date' => $now->copy()->subDays(20), // Antes de hoy
            'end_date' => $now->copy()->addDays(10),
        ]);

        // Attach all concepts to student
        $olderConcept->users()->attach($student->id);
        $newerConcept->users()->attach($student->id);
        $latestConcept->users()->attach($student->id);

        // Act
        $result = $this->repository->getPendingWithDetailsForStudents([$student->id]);

        // Assert - Should be ordered by created_at desc (latest first)
        $this->assertCount(3, $result);
        $this->assertEquals('Latest Concept', $result[0]->concept_name);
        $this->assertEquals('Newer Concept', $result[1]->concept_name);
        $this->assertEquals('Older Concept', $result[2]->concept_name);
    }

}
