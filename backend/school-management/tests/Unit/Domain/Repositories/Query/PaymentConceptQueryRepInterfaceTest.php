<?php

namespace Tests\Unit\Domain\Repositories\Query;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use Tests\Stubs\Repositories\Query\PaymentConceptQueryRepStub;
use Tests\Unit\Domain\Repositories\BaseRepositoryTestCase;
use App\Core\Domain\Repositories\Query\Payments\PaymentConceptQueryRepInterface;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptToDisplay;
use App\Core\Application\DTO\Response\PaymentConcept\PendingSummaryResponse;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptsToDashboardResponse;
use App\Core\Application\DTO\Response\PaymentConcept\PendingPaymentConceptsResponse;
use App\Core\Application\DTO\Response\PaymentConcept\ConceptNameAndAmountResponse;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;

class PaymentConceptQueryRepInterfaceTest extends BaseRepositoryTestCase
{
    protected string $interfaceClass = PaymentConceptQueryRepInterface::class;
    private User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PaymentConceptQueryRepStub();

        // Crear usuario de prueba
        $this->testUser = new User(
            curp: '12345678',
            name: 'Juan',
            last_name: 'Pérez',
            email: 'juan@example.com',
            password: 'password',
            phone_number: "+527352770097",
            status: UserStatus::ACTIVO,
            roles: [UserRoles::STUDENT],
            id: 1
        );
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertNotNull($this->repository);
        $this->assertImplementsInterface($this->interfaceClass);
    }

    #[Test]
    public function it_has_all_required_methods(): void
    {
        $methods = [
            'findById',
            'findByIdToDisplay',
            'getPendingPaymentConcepts',
            'getOverduePaymentsSummary',
            'findAllConcepts',
            'getAllPendingPaymentAmount',
            'getConceptsToDashboard',
            'getPendingPaymentConceptsWithDetails',
            'getOverduePayments',
            'getPendingWithDetailsForStudents'
        ];

        foreach ($methods as $method) {
            $this->assertMethodExists($method);
        }
    }

    #[Test]
    public function findById_returns_payment_concept_when_found(): void
    {
        $concept = new PaymentConcept(
            concept_name: 'Inscripción Semestral',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::parse('2025-01-15'),
            amount: '1500.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
            id: 1,
            description: 'Pago de inscripción para el semestre 2025A',
            end_date: Carbon::parse('2025-06-15')
        );

        $this->repository->setNextFindByIdResult($concept);

        $result = $this->repository->findById(1);

        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Inscripción Semestral', $result->concept_name);
        $this->assertEquals('1500.00', $result->amount);
        $this->assertTrue($result->isActive());
    }

    #[Test]
    public function findById_returns_null_when_not_found(): void
    {
        $this->repository->setNextFindByIdResult(null);

        $result = $this->repository->findById(999);

        $this->assertNull($result);
    }

    #[Test]
    public function findByIdToDisplay_returns_concept_to_display_when_found(): void
    {
        $conceptToDisplay = new ConceptToDisplay(
            id: 1,
            concept_name: 'Inscripción Semestral',
            status: 'activo',
            start_date: '2025-01-15',
            amount: '1500.00',
            applies_to: 'todos',
            users: ['12345', '67890'],
            careers: ['Sistemas', 'Administración'],
            semesters: [1, 2, 3],
            exceptionUsers: ['99999'],
            applicantTags: ['aspirante'],
            description: 'Pago de inscripción',
            end_date: '2025-06-15'
        );

        $this->repository->setNextFindByIdToDisplayResult($conceptToDisplay);

        $result = $this->repository->findByIdToDisplay(1);

        $this->assertInstanceOf(ConceptToDisplay::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Inscripción Semestral', $result->concept_name);
        $this->assertEquals('activo', $result->status);
        $this->assertCount(2, $result->careers);
    }

    #[Test]
    public function findByIdToDisplay_returns_null_when_not_found(): void
    {
        $this->repository->setNextFindByIdToDisplayResult(null);

        $result = $this->repository->findByIdToDisplay(999);

        $this->assertNull($result);
    }

    #[Test]
    public function getPendingPaymentConcepts_returns_pending_summary(): void
    {
        $response = new PendingSummaryResponse('4500.00', 3);
        $this->repository->setNextGetPendingPaymentConceptsResult($response);

        $result = $this->repository->getPendingPaymentConcepts($this->testUser, true);

        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        $this->assertEquals('4500.00', $result->totalAmount);
        $this->assertEquals(3, $result->totalCount);
    }

    #[Test]
    public function getPendingPaymentConcepts_returns_empty_summary(): void
    {
        $response = new PendingSummaryResponse('0.00', 0);
        $this->repository->setNextGetPendingPaymentConceptsResult($response);

        $result = $this->repository->getPendingPaymentConcepts($this->testUser, false);

        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        $this->assertEquals('0.00', $result->totalAmount);
        $this->assertEquals(0, $result->totalCount);
    }

    #[Test]
    public function getOverduePaymentsSummary_returns_overdue_summary(): void
    {
        $response = new PendingSummaryResponse('2500.00', 2);
        $this->repository->setNextGetOverduePaymentsSummaryResult($response);

        $result = $this->repository->getOverduePaymentsSummary($this->testUser, true);

        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        $this->assertEquals('2500.00', $result->totalAmount);
        $this->assertEquals(2, $result->totalCount);
    }

    #[Test]
    public function findAllConcepts_returns_paginated_concepts(): void
    {
        $items = [
            new ConceptsToDashboardResponse(1, 'Concepto 1', 'activo', '1000.00', 'todos', '2025-01-01', '2025-12-31'),
            new ConceptsToDashboardResponse(2, 'Concepto 2', 'activo', '1500.00', 'carreras', '2025-01-01', '2025-12-31'),
        ];

        $paginator = new LengthAwarePaginator($items, 10, 5, 1);
        $this->repository->setNextFindAllConceptsResult($paginator);

        $result = $this->repository->findAllConcepts('activo', 5, 1);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result->items());
        $this->assertEquals(10, $result->total());
        $this->assertEquals(5, $result->perPage());
    }

    #[Test]
    public function getAllPendingPaymentAmount_returns_total_pending(): void
    {
        $response = new PendingSummaryResponse('15000.00', 10);
        $this->repository->setNextGetAllPendingPaymentAmountResult($response);

        $result = $this->repository->getAllPendingPaymentAmount(true);

        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        $this->assertEquals('15000.00', $result->totalAmount);
        $this->assertEquals(10, $result->totalCount);
    }

    #[Test]
    public function getConceptsToDashboard_returns_paginated_dashboard_concepts(): void
    {
        $items = [
            new ConceptsToDashboardResponse(1, 'Inscripción', 'activo', '1500.00', 'todos', '2025-01-01', '2025-06-30'),
            new ConceptsToDashboardResponse(2, 'Colegiatura', 'activo', '2500.00', 'todos', '2025-01-01', '2025-06-30'),
        ];

        $paginator = new LengthAwarePaginator($items, 20, 10, 1);
        $this->repository->setNextGetConceptsToDashboardResult($paginator);

        $result = $this->repository->getConceptsToDashboard(true, 10, 1);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result->items());
        $this->assertEquals(20, $result->total());
        $this->assertEquals(10, $result->perPage());
    }

    #[Test]
    public function getPendingPaymentConceptsWithDetails_returns_array_of_concepts(): void
    {
        $concepts = [
            new PendingPaymentConceptsResponse(1, 'Inscripción', 'Pago de inscripción semestral', '1500.00', '2025-01-15', '2025-06-15'),
            new PendingPaymentConceptsResponse(2, 'Colegiatura', 'Pago de colegiatura mensual', '2500.00', '2025-02-01', '2025-02-28'),
        ];

        $this->repository->setNextGetPendingPaymentConceptsWithDetailsResult($concepts);

        $result = $this->repository->getPendingPaymentConceptsWithDetails($this->testUser);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(PendingPaymentConceptsResponse::class, $result);
        $this->assertEquals('Inscripción', $result[0]->concept_name);
        $this->assertEquals('1500.00', $result[0]->amount);
    }

    #[Test]
    public function getOverduePayments_returns_array_of_overdue_concepts(): void
    {
        $concepts = [
            new PendingPaymentConceptsResponse(3, 'Mensualidad Vencida', 'Mensualidad del mes pasado', '1200.00', '2025-01-01', '2025-01-31'),
        ];

        $this->repository->setNextGetOverduePaymentsResult($concepts);

        $result = $this->repository->getOverduePayments($this->testUser);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertContainsOnlyInstancesOf(PendingPaymentConceptsResponse::class, $result);
        $this->assertEquals('Mensualidad Vencida', $result[0]->concept_name);
    }

    #[Test]
    public function getPendingWithDetailsForStudents_returns_array_of_concepts(): void
    {
        $concepts = [
            new ConceptNameAndAmountResponse('Juan Pérez', 'Inscripción', '1500.00'),
            new ConceptNameAndAmountResponse('María López', 'Colegiatura', '2500.00'),
        ];

        $this->repository->setNextGetPendingWithDetailsForStudentsResult($concepts);

        $result = $this->repository->getPendingWithDetailsForStudents([1, 2]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(ConceptNameAndAmountResponse::class, $result);
        $this->assertEquals('Juan Pérez', $result[0]->user_name);
        $this->assertEquals('1500.00', $result[0]->amount);
    }

    #[Test]
    public function payment_concept_entity_methods_work_correctly(): void
    {
        $concept = new PaymentConcept(
            concept_name: 'Test Concept',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::parse('2025-01-01'),
            amount: '1000.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
            userIds: [1, 2, 3],
            careerIds: [1, 2],
            semesters: [1, 2, 3],
            exceptionUserIds: [4, 5],
            applicantTags: [PaymentConceptApplicantType::APPLICANT, PaymentConceptApplicantType::NO_STUDENT_DETAILS],
            id: 1,
            description: 'Test description',
            end_date: Carbon::parse('2025-12-31')
        );

        // Test entity methods
        $this->assertTrue($concept->isActive());
        $this->assertTrue($concept->isExpired());
        $this->assertTrue($concept->isGlobal());
        $this->assertTrue($concept->hasUser(1));
        $this->assertTrue($concept->hasCareer(1));
        $this->assertTrue($concept->hasSemester(1));
        $this->assertTrue($concept->hasExceptionForUser(4));
        $this->assertTrue($concept->hasTag(PaymentConceptApplicantType::APPLICANT));

        // Test toResponse
        $response = $concept->toResponse();
        $this->assertIsArray($response);
        $this->assertEquals('Test Concept', $response['concept_name']);
        $this->assertEquals('activo', $response['status']);
    }

    #[Test]
    public function methods_have_correct_signatures(): void
    {
        $this->assertMethodParameterType('findById', 'int');
        $this->assertMethodParameterType('findByIdToDisplay', 'int');
        $this->assertMethodParameterType('getPendingPaymentConcepts', User::class);
        $this->assertMethodParameterType('getPendingPaymentConcepts', 'bool', 1);
        $this->assertMethodParameterCount('findAllConcepts', 3);

        $this->assertMethodReturnType('findById', PaymentConcept::class);
        $this->assertMethodReturnType('findByIdToDisplay', ConceptToDisplay::class);
        $this->assertMethodReturnType('getPendingPaymentConcepts', PendingSummaryResponse::class);
        $this->assertMethodReturnType('findAllConcepts', LengthAwarePaginator::class);
        $this->assertMethodReturnType('getPendingPaymentConceptsWithDetails', 'array');
    }

    #[Test]
    public function payment_concept_without_end_date(): void
    {
        $concept = new PaymentConcept(
            concept_name: 'Concepto Sin Fecha Fin',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::parse('2025-01-01'),
            amount: '500.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
            id: 2,
            description: 'Concepto sin fecha de fin',
            end_date: null
        );

        $this->repository->setNextFindByIdResult($concept);

        $result = $this->repository->findById(2);

        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertFalse($result->isExpired());
        $this->assertNull($result->end_date);
    }

    #[Test]
    public function empty_arrays_returned_when_no_data(): void
    {
        $this->repository->setNextGetPendingPaymentConceptsWithDetailsResult([]);
        $this->repository->setNextGetOverduePaymentsResult([]);
        $this->repository->setNextGetPendingWithDetailsForStudentsResult([]);

        $result1 = $this->repository->getPendingPaymentConceptsWithDetails($this->testUser);
        $result2 = $this->repository->getOverduePayments($this->testUser);
        $result3 = $this->repository->getPendingWithDetailsForStudents([]);

        $this->assertIsArray($result1);
        $this->assertEmpty($result1);

        $this->assertIsArray($result2);
        $this->assertEmpty($result2);

        $this->assertIsArray($result3);
        $this->assertEmpty($result3);
    }
}
