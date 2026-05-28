<?php

namespace Tests\Unit\Infraestructure\Repositories\Query;

use App\Core\Domain\Entities\Payment;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Models\Payment as EloquentPayment;
use App\Models\User;
use App\Models\PaymentConcept;
use App\Core\Infraestructure\Repositories\Query\Payments\EloquentPaymentQueryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentPaymentQueryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentPaymentQueryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentPaymentQueryRepository();
    }

    // ==================== FIND BY ID TESTS ====================

    #[Test]
    public function find_by_id_successfully(): void
    {
        // Arrange
        $payment = EloquentPayment::factory()->create();

        // Act
        $result = $this->repository->findById($payment->id);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals($payment->id, $result->id);
        $this->assertEquals($payment->payment_intent_id, $result->payment_intent_id);
    }

    #[Test]
    public function find_by_id_returns_null_for_nonexistent_id(): void
    {
        // Act
        $result = $this->repository->findById(999999);

        // Assert
        $this->assertNull($result);
    }

    // ==================== FIND BY SESSION ID TESTS ====================

    #[Test]
    public function find_by_session_id_successfully(): void
    {
        // Arrange
        $sessionId = 'cs_' . fake()->regexify('[A-Za-z0-9]{24}');
        $payment = EloquentPayment::factory()->create([
            'stripe_session_id' => $sessionId
        ]);

        // Act
        $result = $this->repository->findBySessionId($sessionId);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals($payment->id, $result->id);
        $this->assertEquals($sessionId, $result->stripe_session_id);
    }

    #[Test]
    public function find_by_session_id_returns_null_for_nonexistent(): void
    {
        // Act
        $result = $this->repository->findBySessionId('cs_nonexistent');

        // Assert
        $this->assertNull($result);
    }

    // ==================== FIND BY INTENT ID TESTS ====================

    #[Test]
    public function find_by_intent_id_successfully(): void
    {
        // Arrange
        $intentId = 'pi_' . fake()->regexify('[A-Za-z0-9]{24}');
        $payment = EloquentPayment::factory()->create([
            'payment_intent_id' => $intentId
        ]);

        // Act
        $result = $this->repository->findByIntentId($intentId);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals($payment->id, $result->id);
        $this->assertEquals($intentId, $result->payment_intent_id);
    }

    #[Test]
    public function find_by_intent_id_returns_null_for_nonexistent(): void
    {
        // Act
        $result = $this->repository->findByIntentId('pi_nonexistent');

        // Assert
        $this->assertNull($result);
    }

    // ==================== SUM PAYMENTS BY USER YEAR TESTS ====================

    #[Test]
    public function sum_payments_by_user_year_all_time(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Pagos con amount_received para el usuario
        EloquentPayment::factory()->count(3)->create([
            'user_id' => $user->id,
            'amount_received' => 1000,
            'created_at' => now()->subYears(2) // Pagos de años anteriores
        ]);

        EloquentPayment::factory()->count(2)->create([
            'user_id' => $user->id,
            'amount_received' => 2000,
            'created_at' => now() // Pagos actuales
        ]);

        // Pagos sin amount_received (no deben contar)
        EloquentPayment::factory()->create([
            'user_id' => $user->id,
            'amount_received' => null
        ]);

        // Pagos para otro usuario (no deben contar)
        EloquentPayment::factory()->count(2)->create([
            'amount_received' => 1500
        ]);

        // Act
        $result = $this->repository->sumPaymentsByUserYear($user->id, false);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_month', $result);

        // Total esperado: (3 * 1000) + (2 * 2000) = 3000 + 4000 = 7000
        $this->assertEquals('7000.00', $result['total']);
        $this->assertNotEmpty($result['by_month']);
    }

    #[Test]
    public function sum_payments_by_user_year_only_this_year(): void
    {
        // Arrange
        $user = User::factory()->create();
        $currentYear = now()->year;

        // Pagos del año actual
        EloquentPayment::factory()->create([
            'user_id' => $user->id,
            'amount_received' => 3000,
            'created_at' => now()->startOfYear()->addMonths(2)
        ]);

        EloquentPayment::factory()->create([
            'user_id' => $user->id,
            'amount_received' => 4000,
            'created_at' => now()->startOfYear()->addMonths(5)
        ]);

        // Pagos del año anterior (no deben contar)
        EloquentPayment::factory()->create([
            'user_id' => $user->id,
            'amount_received' => 5000,
            'created_at' => now()->subYear()
        ]);

        // Act
        $result = $this->repository->sumPaymentsByUserYear($user->id, true);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals('7000.00', $result['total']);
        $this->assertCount(2, $result['by_month']);
    }

    #[Test]
    public function sum_payments_by_user_year_returns_empty_for_user_without_payments(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->repository->sumPaymentsByUserYear($user->id, false);

        // Assert
        $this->assertEquals('0.00', $result['total']);
        $this->assertEmpty($result['by_month']);
    }

    // ==================== GET ALL PAYMENTS MADE TESTS ====================

    #[Test]
    public function get_all_payments_made_all_time(): void
    {
        // Arrange
        // Pagos con amount_received
        EloquentPayment::factory()->count(3)->create([
            'amount_received' => 1500,
            'created_at' => now()->subYear()
        ]);

        EloquentPayment::factory()->count(2)->create([
            'amount_received' => 2500,
            'created_at' => now()
        ]);

        // Pagos sin amount_received (no deben contar)
        EloquentPayment::factory()->count(2)->create([
            'amount_received' => null
        ]);

        // Act
        $result = $this->repository->getAllPaymentsMade(false);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('by_month', $result);

        // Total esperado: (3 * 1500) + (2 * 2500) = 4500 + 5000 = 9500
        $this->assertEquals('9500.00', $result['total']);
    }

    #[Test]
    public function get_all_payments_made_only_this_year(): void
    {
        // Arrange
        // Pagos del año actual
        EloquentPayment::factory()->create([
            'amount_received' => 3000,
            'created_at' => now()->startOfYear()->addMonth()
        ]);

        // Pagos del año anterior (no deben contar)
        EloquentPayment::factory()->create([
            'amount_received' => 5000,
            'created_at' => now()->subYear()
        ]);

        // Act
        $result = $this->repository->getAllPaymentsMade(true);

        // Assert
        $this->assertEquals('3000.00', $result['total']);
    }

    // ==================== GET PAYMENT HISTORY TESTS ====================

    #[Test]
    public function get_payment_history_all_time(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Crear pagos para el usuario
        $payments = EloquentPayment::factory()->count(15)->create([
            'user_id' => $user->id,
            'created_at' => now()->subYears(2) // Algunos de años anteriores
        ]);

        // Crear pagos para otro usuario
        EloquentPayment::factory()->count(5)->create();

        // Act
        $result = $this->repository->getPaymentHistory($user->id, 10, 1, false);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(15, $result->total()); // Total de pagos del usuario
        $this->assertCount(10, $result->items()); // Página 1 tiene 10 items

    }

    #[Test]
    public function get_payment_history_only_this_year(): void
    {
        // Arrange
        $user = User::factory()->create();
        $currentYear = now()->year;

        // Pagos del año actual
        EloquentPayment::factory()->count(3)->create([
            'user_id' => $user->id,
            'created_at' => now()->startOfYear()->addMonth()
        ]);

        // Pagos del año anterior (no deben aparecer)
        EloquentPayment::factory()->count(2)->create([
            'user_id' => $user->id,
            'created_at' => now()->subYear()
        ]);

        // Act
        $result = $this->repository->getPaymentHistory($user->id, 10, 1, true);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->total()); // Solo los del año actual
    }

    #[Test]
    public function get_payment_history_with_pagination(): void
    {
        // Arrange
        $user = User::factory()->create();
        EloquentPayment::factory()->count(15)->create(['user_id' => $user->id]);

        // Act - Página 2 con 10 por página
        $result = $this->repository->getPaymentHistory($user->id, 3, 2, false);

        // Assert
        $this->assertEquals(15, $result->total());
        $this->assertEquals(2, $result->currentPage());
        $this->assertEquals(3, $result->perPage());
        $this->assertCount(3, $result->items());
    }

    #[Test]
    public function get_payment_history_returns_empty_for_user_without_payments(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->repository->getPaymentHistory($user->id, 10, 1, false);

        // Assert
        $this->assertEquals(0, $result->total());
        $this->assertEmpty($result->items());
    }

    // ==================== GET PAYMENT HISTORY WITH DETAILS TESTS ====================

    #[Test]
    public function get_payment_history_with_details(): void
    {
        // Arrange
        $user = User::factory()->create();
        EloquentPayment::factory()->count(5)->create(['user_id' => $user->id]);

        // Act
        $result = $this->repository->getPaymentHistoryWithDetails($user->id, 10, 1);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(5, $result->total());

        // Verificar que los items tienen los campos esperados
        foreach ($result->items() as $item) {
            $this->assertObjectHasProperty('concept', $item);
            $this->assertObjectHasProperty('amount', $item);
            $this->assertObjectHasProperty('amount_received', $item);
            $this->assertObjectHasProperty('balance', $item);
            $this->assertObjectHasProperty('status', $item);
            $this->assertObjectHasProperty('reference', $item);
            $this->assertObjectHasProperty('payment_method_details', $item);
        }
    }

    #[Test]
    public function get_payment_history_with_details_order(): void
    {
        // Arrange
        $user = User::factory()->create();

        $oldest = EloquentPayment::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(3)
        ]);

        $newest = EloquentPayment::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()
        ]);

        // Act
        $result = $this->repository->getPaymentHistoryWithDetails($user->id, 10, 1);

        // Assert - Debería ordenar por created_at DESC
        $items = $result->items();
        $this->assertEquals($newest->id, $items[0]->id);
        $this->assertEquals($oldest->id, $items[1]->id);
    }

    // ==================== FIND BY INTENT OR SESSION TESTS ====================

    #[Test]
    public function find_by_intent_or_session_with_intent_id(): void
    {
        // Arrange
        $user = User::factory()->create();
        $intentId = 'pi_test_123';
        $payment = EloquentPayment::factory()->create([
            'user_id' => $user->id,
            'payment_intent_id' => $intentId
        ]);

        // Act
        $result = $this->repository->findByIntentOrSession($user->id, $intentId);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals($payment->id, $result->id);
    }

    #[Test]
    public function find_by_intent_or_session_with_session_id(): void
    {
        // Arrange
        $user = User::factory()->create();
        $sessionId = 'cs_test_123';
        $payment = EloquentPayment::factory()->create([
            'user_id' => $user->id,
            'stripe_session_id' => $sessionId
        ]);

        // Act
        $result = $this->repository->findByIntentOrSession($user->id, $sessionId);

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals($payment->id, $result->id);
    }

    #[Test]
    public function find_by_intent_or_session_returns_null_for_wrong_user(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $intentId = 'pi_test_123';

        EloquentPayment::factory()->create([
            'user_id' => $user1->id,
            'payment_intent_id' => $intentId
        ]);

        // Act - Buscar con user2 (dueño incorrecto)
        $result = $this->repository->findByIntentOrSession($user2->id, $intentId);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function find_by_intent_or_session_returns_null_for_nonexistent(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->repository->findByIntentOrSession($user->id, 'nonexistent_id');

        // Assert
        $this->assertNull($result);
    }

    // ==================== GET ALL WITH SEARCH EAGER TESTS ====================

    #[Test]
    public function get_all_with_search_eager_without_search(): void
    {
        // Arrange
        EloquentPayment::factory()->count(10)->create();

        // Act
        $result = $this->repository->getAllWithSearchEager(null, 10, 1);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(10, $result->total());
        $this->assertCount(10, $result->items());
    }

    #[Test]
    public function get_all_with_search_eager_with_search_by_user_name(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'Juan Pérez']);
        $otherUser = User::factory()->create(['name' => 'María López']);

        EloquentPayment::factory()->count(3)->create(['user_id' => $user->id]);
        EloquentPayment::factory()->count(2)->create(['user_id' => $otherUser->id]);

        // Act - Buscar por nombre
        $result = $this->repository->getAllWithSearchEager('Juan', 10, 1);

        // Assert
        $this->assertEquals(3, $result->total());
        foreach ($result->items() as $item) {
            $this->assertEquals($user->name . ' ' . $user->last_name, $item->fullName);
        }
    }

    #[Test]
    public function get_all_with_search_eager_with_search_by_concept_name(): void
    {
        // Arrange
        EloquentPayment::factory()->create(['concept_name' => 'Inscripción Semestral']);
        EloquentPayment::factory()->create(['concept_name' => 'Colegiatura Mensual']);
        EloquentPayment::factory()->create(['concept_name' => 'Material Didáctico']);

        // Act - Buscar por concepto
        $result = $this->repository->getAllWithSearchEager('Inscripción', 10, 1);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertEquals('Inscripción Semestral', $result->items()[0]->concept);
    }

    #[Test]
    public function get_all_with_search_eager_order(): void
    {
        // Arrange
        $oldest = EloquentPayment::factory()->create(['created_at' => now()->subDays(3)]);
        $newest = EloquentPayment::factory()->create(['created_at' => now()]);

        // Act
        $result = $this->repository->getAllWithSearchEager(null, 10, 1);

        // Assert - Debería ordenar por created_at DESC
        $items = $result->items();
        $this->assertEquals($newest->id, $items[0]->id);
        $this->assertEquals($oldest->id, $items[1]->id);
    }

    // ==================== GET PAID WITHIN LAST MONTH CURSOR TESTS ====================

    #[Test]
    public function get_paid_within_last_month_cursor(): void
    {
        // Arrange
        // Pagos reconcilables dentro del último mes
        EloquentPayment::factory()->create([
            'status' => PaymentStatus::PAID->value,
            'created_at' => now()->subDays(15)
        ]);

        EloquentPayment::factory()->create([
            'status' => PaymentStatus::DEFAULT->value,
            'created_at' => now()->subDays(10)
        ]);

        // Pagos no reconcilables (no deben aparecer)
        EloquentPayment::factory()->create([
            'status' => PaymentStatus::SUCCEEDED->value, // No es reconcilable
            'created_at' => now()->subDays(5)
        ]);

        // Pagos fuera del rango de tiempo (no deben aparecer)
        EloquentPayment::factory()->create([
            'status' => PaymentStatus::PAID->value,
            'created_at' => now()->subMonths(2)
        ]);

        // Act
        $generator = $this->repository->getReconciliablePaymentsCursor();
        $results = iterator_to_array($generator);

        // Assert
        $this->assertCount(2, $results);
        foreach ($results as $payment) {
            $this->assertInstanceOf(Payment::class, $payment);
            $this->assertTrue(in_array($payment->status, PaymentStatus::reconcilableStatuses()));
        }
    }

    #[Test]
    public function get_paid_within_last_month_cursor_empty(): void
    {
        // Act
        $generator = $this->repository->getReconciliablePaymentsCursor();
        $results = iterator_to_array($generator);

        // Assert
        $this->assertEmpty($results);
    }

    // ==================== GET LAST PAYMENT FOR CONCEPT TESTS ====================

    #[Test]
    public function get_last_payment_for_concept(): void
    {
        // Arrange
        $user = User::factory()->create();
        $concept = PaymentConcept::factory()->create();

        // Pagos para el concepto
        $oldest = EloquentPayment::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept->id,
            'created_at' => now()->subDays(3)
        ]);

        $newest = EloquentPayment::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept->id,
            'created_at' => now()
        ]);

        // Pago para otro concepto (no debe aparecer)
        EloquentPayment::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => PaymentConcept::factory()->create()->id
        ]);

        // Act
        $result = $this->repository->getLastPaymentForConcept($user->id, $concept->id);

        // Assert - Debería devolver el más reciente
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals($newest->id, $result->id);
    }

    #[Test]
    public function get_last_payment_for_concept_with_status_filter(): void
    {
        // Arrange
        $user = User::factory()->create();
        $concept = PaymentConcept::factory()->create();

        // Pago con status no permitido
        EloquentPayment::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept->id,
            'status' => PaymentStatus::SUCCEEDED->value
        ]);

        // Pago con status permitido
        $allowedPayment = EloquentPayment::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept->id,
            'status' => PaymentStatus::PAID->value
        ]);

        // Act - Buscar solo pagos con status PAID
        $result = $this->repository->getLastPaymentForConcept(
            $user->id,
            $concept->id,
            [PaymentStatus::PAID->value]
        );

        // Assert
        $this->assertInstanceOf(Payment::class, $result);
        $this->assertEquals($allowedPayment->id, $result->id);
    }

    #[Test]
    public function get_last_payment_for_concept_returns_null_for_no_payments(): void
    {
        // Arrange
        $user = User::factory()->create();
        $concept = PaymentConcept::factory()->create();

        // Act
        $result = $this->repository->getLastPaymentForConcept($user->id, $concept->id);

        // Assert
        $this->assertNull($result);
    }

    // ==================== GET PAYMENTS BY CONCEPT NAME TESTS ====================

    #[Test]
    public function get_payments_by_concept_name(): void
    {
        // Arrange
        // Pagos para diferentes conceptos
        EloquentPayment::factory()->count(2)->create([
            'concept_name' => 'Inscripción Semestral',
            'amount' => 5000,
            'amount_received' => 5000
        ]);

        EloquentPayment::factory()->count(3)->create([
            'concept_name' => 'Colegiatura Mensual',
            'amount' => 3000,
            'amount_received' => 3000
        ]);

        // Act
        $result = $this->repository->getPaymentsByConceptName(null, 10, 1);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(2, $result->total()); // 2 conceptos diferentes

        $items = $result->items();

        // Verificar el primer concepto
        $this->assertEquals('Inscripción Semestral', $items[0]->concept_name);
        $this->assertEquals('10000.00', $items[0]->amount_total); // 2 * 5000
        $this->assertEquals('10000.00', $items[0]->amount_received_total);

        // Verificar el segundo concepto
        $this->assertEquals('Colegiatura Mensual', $items[1]->concept_name);
        $this->assertEquals('9000.00', $items[1]->amount_total); // 3 * 3000
    }

    #[Test]
    public function get_payments_by_concept_name_with_search(): void
    {
        // Arrange
        EloquentPayment::factory()->create(['concept_name' => 'Inscripción Semestral']);
        EloquentPayment::factory()->create(['concept_name' => 'Colegiatura Mensual']);
        EloquentPayment::factory()->create(['concept_name' => 'Material Didáctico']);

        // Act - Buscar conceptos que contengan "Semestral"
        $result = $this->repository->getPaymentsByConceptName('Semestral', 10, 1);

        // Assert
        $this->assertEquals(1, $result->total());
        $this->assertEquals('Inscripción Semestral', $result->items()[0]->concept_name);
    }

    #[Test]
    public function get_payments_by_concept_name_order(): void
    {
        // Arrange
        $oldestConcept = EloquentPayment::factory()->create([
            'concept_name' => 'Concepto Antiguo',
            'created_at' => now()->subDays(10)
        ]);

        $newestConcept = EloquentPayment::factory()->create([
            'concept_name' => 'Concepto Reciente',
            'created_at' => now()
        ]);

        // Act
        $result = $this->repository->getPaymentsByConceptName(null, 10, 1);

        // Assert - Ordenado por última fecha de pago DESC
        $items = $result->items();
        $this->assertEquals('Concepto Reciente', $items[0]->concept_name);
        $this->assertEquals('Concepto Antiguo', $items[1]->concept_name);
    }

    // ==================== EDGE CASES TESTS ====================

    #[Test]
    public function sum_payments_formats_numbers_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Montos con muchos decimales
        EloquentPayment::factory()->create([
            'user_id' => $user->id,
            'amount_received' => 1234.5678,
            'created_at' => now()->startOfYear()->addMonth(1)
        ]);

        EloquentPayment::factory()->create([
            'user_id' => $user->id,
            'amount_received' => 987.6543,
            'created_at' => now()->startOfYear()->addMonth(2)
        ]);

        // Act
        $result = $this->repository->sumPaymentsByUserYear($user->id, true);

        // Assert - Debería formatear a 2 decimales
        $total = 1234.5678 + 987.6543; // 2222.2221
        $this->assertEquals('2222.22', $result['total']);

        foreach ($result['by_month'] as $monthTotal) {
            $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $monthTotal);
        }
    }

    #[Test]
    public function monthly_aggregation_grouping(): void
    {
        // Arrange
        $user = User::factory()->create();
        $yearMonth = now()->format('Y-m');

        // Pagos en el mismo mes
        EloquentPayment::factory()->create([
            'user_id' => $user->id,
            'amount_received' => 1000,
            'created_at' => now()->startOfMonth()
        ]);

        EloquentPayment::factory()->create([
            'user_id' => $user->id,
            'amount_received' => 2000,
            'created_at' => now()->endOfMonth()
        ]);

        // Act
        $result = $this->repository->sumPaymentsByUserYear($user->id, true);

        // Assert - Ambos pagos deberían agruparse en el mismo mes
        $this->assertCount(1, $result['by_month']);
        $this->assertArrayHasKey($yearMonth, $result['by_month']);
        $this->assertEquals('3000.00', $result['by_month'][$yearMonth]);
    }

    #[Test]
    public function pagination_edge_cases(): void
    {
        // Arrange
        $user = User::factory()->create();
        EloquentPayment::factory()->count(5)->create(['user_id' => $user->id]);

        // Act - Página fuera de rango
        $result = $this->repository->getPaymentHistory($user->id, 10, 99, false);

        // Assert - Debería devolver página vacía
        $this->assertEmpty($result->items());
        $this->assertEquals(5, $result->total());
    }

    #[Test]
    public function search_with_special_characters(): void
    {
        // Arrange
        $user = User::factory()->create(['name' => 'José María']);
        EloquentPayment::factory()->create(['user_id' => $user->id]);

        // Act - Buscar con caracteres especiales
        $result = $this->repository->getAllWithSearchEager('José', 10, 1);

        // Assert
        $this->assertEquals(1, $result->total());
    }

    // ==================== INTEGRATION TESTS ====================

    #[Test]
    public function complete_payment_query_scenarios(): void
    {
        // 1. Crear usuarios y pagos
        $user1 = User::factory()->create(['name' => 'Usuario Uno']);
        $user2 = User::factory()->create(['name' => 'Usuario Dos']);

        $payment1 = EloquentPayment::factory()->create([
            'user_id' => $user1->id,
            'payment_intent_id' => 'pi_1',
            'stripe_session_id' => 'cs_1',
            'concept_name' => 'Inscripción',
            'amount_received' => 1000,
            'status' => PaymentStatus::PAID->value,
            'created_at' => now()->subDays(5)
        ]);

        $payment2 = EloquentPayment::factory()->create([
            'user_id' => $user1->id,
            'payment_intent_id' => 'pi_2',
            'concept_name' => 'Colegiatura',
            'amount_received' => 2000,
            'status' => PaymentStatus::SUCCEEDED->value,
            'created_at' => now()->subDays(10)
        ]);

        // 2. Probar findById
        $byId = $this->repository->findById($payment1->id);
        $this->assertEquals($payment1->id, $byId->id);

        // 3. Probar findBySessionId
        $bySession = $this->repository->findBySessionId('cs_1');
        $this->assertEquals($payment1->id, $bySession->id);

        // 4. Probar findByIntentId
        $byIntent = $this->repository->findByIntentId('pi_2');
        $this->assertEquals($payment2->id, $byIntent->id);

        // 5. Probar sumPaymentsByUserYear
        $sum = $this->repository->sumPaymentsByUserYear($user1->id, false);
        $this->assertEquals('3000.00', $sum['total']);

        // 6. Probar getPaymentHistory
        $history = $this->repository->getPaymentHistory($user1->id, 10, 1, false);
        $this->assertEquals(2, $history->total());

        // 7. Probar findByIntentOrSession
        $intentOrSession = $this->repository->findByIntentOrSession($user1->id, 'pi_1');
        $this->assertEquals($payment1->id, $intentOrSession->id);

        // 8. Probar getAllWithSearchEager
        $searchResults = $this->repository->getAllWithSearchEager('Usuario Uno', 10, 1);
        $this->assertEquals(2, $searchResults->total());

        // 9. Probar getPaymentsByConceptName
        $byConcept = $this->repository->getPaymentsByConceptName(null, 10, 1);
        $this->assertGreaterThanOrEqual(2, $byConcept->total());
    }

    #[Test]
    public function repository_handles_large_datasets(): void
    {
        // Arrange - Crear muchos pagos
        $user = User::factory()->create();
        EloquentPayment::factory()->count(100)->create(['user_id' => $user->id]);

        // Crear pagos para otros usuarios
        EloquentPayment::factory()->count(50)->create();

        // Act - Probar paginación
        $result = $this->repository->getPaymentHistory($user->id, 20, 1, false);

        // Assert
        $this->assertEquals(100, $result->total());
        $this->assertCount(20, $result->items());

        // Probar cursor
        $cursor = $this->repository->getReconciliablePaymentsCursor();
        $this->assertIsIterable($cursor);
    }

}
