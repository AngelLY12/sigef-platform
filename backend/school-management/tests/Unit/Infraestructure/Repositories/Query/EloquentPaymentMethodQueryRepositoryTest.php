<?php

namespace Tests\Unit\Infraestructure\Repositories\Query;

use App\Core\Infraestructure\Repositories\Query\Payments\EloquentPaymentMethodQueryRepository;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\PaymentMethod as EloquentPaymentMethod;
use App\Models\User;
use App\Core\Domain\Entities\PaymentMethod;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentPaymentMethodQueryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentPaymentMethodQueryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentPaymentMethodQueryRepository();
    }

    // ==================== FIND BY ID TESTS ====================

    #[Test]
    public function find_by_id_successfully(): void
    {
        // Arrange
        $paymentMethod = EloquentPaymentMethod::factory()->create();

        // Act
        $result = $this->repository->findById($paymentMethod->id);

        // Assert
        $this->assertInstanceOf(PaymentMethod::class, $result);
        $this->assertEquals($paymentMethod->id, $result->id);
        $this->assertEquals($paymentMethod->stripe_payment_method_id, $result->stripe_payment_method_id);
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
    public function find_by_id_returns_null_for_zero_id(): void
    {
        // Act
        $result = $this->repository->findById(0);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function find_by_id_returns_null_for_negative_id(): void
    {
        // Act
        $result = $this->repository->findById(-1);

        // Assert
        $this->assertNull($result);
    }

    // ==================== FIND BY STRIPE ID TESTS ====================

    #[Test]
    public function find_by_stripe_id_successfully(): void
    {
        // Arrange
        $stripeId = 'pm_' . fake()->regexify('[A-Za-z0-9]{24}');
        $paymentMethod = EloquentPaymentMethod::factory()->create([
            'stripe_payment_method_id' => $stripeId
        ]);

        // Act
        $result = $this->repository->findByStripeId($stripeId);

        // Assert
        $this->assertInstanceOf(PaymentMethod::class, $result);
        $this->assertEquals($paymentMethod->id, $result->id);
        $this->assertEquals($stripeId, $result->stripe_payment_method_id);
    }

    #[Test]
    public function find_by_stripe_id_with_exact_match(): void
    {
        // Arrange
        $stripeId = 'pm_' . fake()->regexify('[A-Za-z0-9]{24}');
        $partialStripeId = substr($stripeId, 0, 10);

        EloquentPaymentMethod::factory()->create([
            'stripe_payment_method_id' => $stripeId
        ]);

        // Act - Búsqueda parcial debería fallar
        $result = $this->repository->findByStripeId($partialStripeId);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function find_by_stripe_id_returns_null_for_nonexistent_stripe_id(): void
    {
        // Act
        $result = $this->repository->findByStripeId('pm_1234567890abcdefghijklmn');

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function find_by_stripe_id_returns_null_for_empty_string(): void
    {
        // Act
        $result = $this->repository->findByStripeId('');

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function find_by_stripe_id_with_special_characters(): void
    {
        // Arrange - Los IDs de Stripe no suelen tener caracteres especiales, pero por si acaso
        $stripeId = 'pm_test_' . fake()->regexify('[A-Za-z0-9]{20}');
        $paymentMethod = EloquentPaymentMethod::factory()->create([
            'stripe_payment_method_id' => $stripeId
        ]);

        // Act
        $result = $this->repository->findByStripeId($stripeId);

        // Assert
        $this->assertInstanceOf(PaymentMethod::class, $result);
        $this->assertEquals($stripeId, $result->stripe_payment_method_id);
    }

    // ==================== FIND BY STRIPE IDS TESTS ====================

    #[Test]
    public function find_by_stripe_ids_successfully(): void
    {
        // Arrange
        $stripeIds = [
            'pm_' . fake()->regexify('[A-Za-z0-9]{24}'),
            'pm_' . fake()->regexify('[A-Za-z0-9]{24}'),
            'pm_' . fake()->regexify('[A-Za-z0-9]{24}')
        ];

        foreach ($stripeIds as $stripeId) {
            EloquentPaymentMethod::factory()->create([
                'stripe_payment_method_id' => $stripeId
            ]);
        }

        // Crear un método de pago que NO debe ser devuelto
        EloquentPaymentMethod::factory()->create([
            'stripe_payment_method_id' => 'pm_excluded_' . fake()->regexify('[A-Za-z0-9]{20}')
        ]);

        // Act
        $result = $this->repository->findByStripeIds($stripeIds);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        foreach ($result as $paymentMethod) {
            $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
            $this->assertContains($paymentMethod->stripe_payment_method_id, $stripeIds);
        }
    }

    #[Test]
    public function find_by_stripe_ids_returns_empty_array_for_empty_array(): void
    {
        // Act
        $result = $this->repository->findByStripeIds([]);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function find_by_stripe_ids_returns_empty_array_when_no_matches(): void
    {
        // Arrange
        $nonExistentIds = ['pm_nonexistent1', 'pm_nonexistent2'];

        // Act
        $result = $this->repository->findByStripeIds($nonExistentIds);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function find_by_stripe_ids_returns_only_matching_records(): void
    {
        // Arrange
        $matchingIds = ['pm_match1', 'pm_match2'];
        $nonMatchingIds = ['pm_nomatch1', 'pm_nomatch2'];

        foreach ($matchingIds as $id) {
            EloquentPaymentMethod::factory()->create(['stripe_payment_method_id' => $id]);
        }

        foreach ($nonMatchingIds as $id) {
            EloquentPaymentMethod::factory()->create(['stripe_payment_method_id' => $id]);
        }

        // Act - Buscar solo los que coinciden
        $result = $this->repository->findByStripeIds($matchingIds);

        // Assert
        $this->assertCount(2, $result);

        $returnedIds = array_map(
            fn($pm) => $pm->stripe_payment_method_id,
            $result
        );

        sort($returnedIds);
        sort($matchingIds);

        $this->assertEquals($matchingIds, $returnedIds);
    }

    #[Test]
    public function find_by_stripe_ids_with_duplicate_ids_in_query(): void
    {
        // Arrange
        $stripeId = 'pm_' . fake()->regexify('[A-Za-z0-9]{24}');
        $paymentMethod = EloquentPaymentMethod::factory()->create([
            'stripe_payment_method_id' => $stripeId
        ]);

        // Act - Query con IDs duplicados
        $result = $this->repository->findByStripeIds([$stripeId, $stripeId, $stripeId]);

        // Assert - Debería devolver solo un resultado único
        $this->assertCount(1, $result);
        $this->assertEquals($stripeId, $result[0]->stripe_payment_method_id);
    }

    // ==================== GET BY USER ID TESTS ====================

    #[Test]
    public function get_by_user_id_successfully(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Crear métodos de pago para el usuario
        $paymentMethods = EloquentPaymentMethod::factory()->count(3)->create([
            'user_id' => $user->id
        ]);

        // Crear métodos de pago para otros usuarios (no deberían aparecer)
        EloquentPaymentMethod::factory()->count(2)->create();

        // Act
        $result = $this->repository->getByUserId($user->id);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        foreach ($result as $paymentMethod) {
            $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
            $this->assertEquals($user->id, $paymentMethod->user_id);
        }
    }

    #[Test]
    public function get_by_user_id_returns_empty_array_for_user_without_payment_methods(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->repository->getByUserId($user->id);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function get_by_user_id_returns_payment_methods_in_correct_order(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Crear métodos de pago con diferentes fechas de creación
        $oldest = EloquentPaymentMethod::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(3)
        ]);

        $middle = EloquentPaymentMethod::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(1)
        ]);

        $newest = EloquentPaymentMethod::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()
        ]);

        // Act
        $result = $this->repository->getByUserId($user->id);

        // Assert - Deberían venir ordenados por created_at DESC (más recientes primero)
        $this->assertCount(3, $result);

        // El primero debería ser el más reciente
        $this->assertEquals($newest->id, $result[0]->id);

        // El segundo debería ser el del medio
        $this->assertEquals($middle->id, $result[1]->id);

        // El último debería ser el más antiguo
        $this->assertEquals($oldest->id, $result[2]->id);
    }

    #[Test]
    public function get_by_user_id_for_nonexistent_user(): void
    {
        // Act
        $result = $this->repository->getByUserId(999999);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function get_by_user_id_with_zero_user_id(): void
    {
        // Act
        $result = $this->repository->getByUserId(0);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function get_by_user_id_with_negative_user_id(): void
    {
        // Act
        $result = $this->repository->getByUserId(-1);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ==================== COMPARISON TESTS ====================

    #[Test]
    public function find_by_id_and_find_by_stripe_id_return_same_payment_method(): void
    {
        // Arrange
        $stripeId = 'pm_' . fake()->regexify('[A-Za-z0-9]{24}');
        $paymentMethod = EloquentPaymentMethod::factory()->create([
            'stripe_payment_method_id' => $stripeId
        ]);

        // Act
        $resultById = $this->repository->findById($paymentMethod->id);
        $resultByStripeId = $this->repository->findByStripeId($stripeId);

        // Assert
        $this->assertNotNull($resultById);
        $this->assertNotNull($resultByStripeId);
        $this->assertEquals($resultById->id, $resultByStripeId->id);
        $this->assertEquals($resultById->stripe_payment_method_id, $resultByStripeId->stripe_payment_method_id);
    }

    #[Test]
    public function get_by_user_id_includes_payment_method_found_by_stripe_ids(): void
    {
        // Arrange
        $user = User::factory()->create();
        $stripeIds = ['pm_id1', 'pm_id2', 'pm_id3'];

        foreach ($stripeIds as $stripeId) {
            EloquentPaymentMethod::factory()->create([
                'user_id' => $user->id,
                'stripe_payment_method_id' => $stripeId
            ]);
        }

        // Act
        $userPaymentMethods = $this->repository->getByUserId($user->id);
        $byStripeIds = $this->repository->findByStripeIds($stripeIds);

        // Assert
        $this->assertCount(3, $userPaymentMethods);
        $this->assertCount(3, $byStripeIds);

        // Verificar que todos los métodos de pago por stripe_ids están en los del usuario
        $userStripeIds = array_map(
            fn($pm) => $pm->stripe_payment_method_id,
            $userPaymentMethods
        );

        $byStripeIdsIds = array_map(
            fn($pm) => $pm->stripe_payment_method_id,
            $byStripeIds
        );

        sort($userStripeIds);
        sort($byStripeIdsIds);

        $this->assertEquals($userStripeIds, $byStripeIdsIds);
    }

    // ==================== EDGE CASES TESTS ====================

    #[Test]
    public function payment_method_with_long_stripe_id(): void
    {
        // Arrange
        $longStripeId = 'pm_test_' . str_repeat('a', 42);
        $paymentMethod = EloquentPaymentMethod::factory()->create([
            'stripe_payment_method_id' => $longStripeId
        ]);

        // Act
        $result = $this->repository->findByStripeId($longStripeId);

        // Assert
        $this->assertInstanceOf(PaymentMethod::class, $result);
        $this->assertEquals($longStripeId, $result->stripe_payment_method_id);
    }

    #[Test]
    public function multiple_payment_methods_with_similar_stripe_ids(): void
    {
        // Arrange
        $baseStripeId = 'pm_test_1234567890';
        $variations = [
            $baseStripeId . '_a',
            $baseStripeId . '_b',
            $baseStripeId . '_c'
        ];

        foreach ($variations as $stripeId) {
            EloquentPaymentMethod::factory()->create(['stripe_payment_method_id' => $stripeId]);
        }

        // Act - Buscar uno específico
        $targetId = $baseStripeId . '_b';
        $result = $this->repository->findByStripeId($targetId);

        // Assert
        $this->assertInstanceOf(PaymentMethod::class, $result);
        $this->assertEquals($targetId, $result->stripe_payment_method_id);
    }

    #[Test]
    public function get_by_user_id_after_deleting_payment_methods(): void
    {
        // Arrange
        $user = User::factory()->create();

        $pm1 = EloquentPaymentMethod::factory()->create(['user_id' => $user->id]);
        $pm2 = EloquentPaymentMethod::factory()->create(['user_id' => $user->id]);
        $pm3 = EloquentPaymentMethod::factory()->create(['user_id' => $user->id]);

        // Act - Eliminar un método de pago
        $pm2->delete();

        $result = $this->repository->getByUserId($user->id);

        // Assert
        $this->assertCount(2, $result);

        $paymentMethodIds = array_map(fn($pm) => $pm->id, $result);
        $this->assertContains($pm1->id, $paymentMethodIds);
        $this->assertContains($pm3->id, $paymentMethodIds);
        $this->assertNotContains($pm2->id, $paymentMethodIds);
    }

    // ==================== PERFORMANCE TESTS ====================

    #[Test]
    public function get_by_user_id_with_large_number_of_payment_methods(): void
    {
        // Arrange
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $paymentMethodCount = 15;

        // Crear métodos de pago para el usuario objetivo
        EloquentPaymentMethod::factory()->count($paymentMethodCount)->create([
            'user_id' => $user->id
        ]);

        // Crear métodos de pago para otro usuario (no deberían aparecer)
        EloquentPaymentMethod::factory()->count(10)->create([
            'user_id' => $otherUser->id
        ]);

        // Act
        $result = $this->repository->getByUserId($user->id);

        // Assert
        $this->assertCount($paymentMethodCount, $result);

        // Verificar que todos son instancias de PaymentMethod y pertenecen al usuario correcto
        foreach ($result as $paymentMethod) {
            $this->assertInstanceOf(PaymentMethod::class, $paymentMethod);
            $this->assertEquals($user->id, $paymentMethod->user_id);
        }
    }

    #[Test]
    public function find_by_stripe_ids_performance_with_many_records(): void
    {
        // Arrange
        $targetIds = ['pm_target1', 'pm_target2', 'pm_target3'];

        // Crear muchos métodos de pago
        EloquentPaymentMethod::factory()->count(20)->create();

        // Crear los métodos de pago objetivo al final
        foreach ($targetIds as $stripeId) {
            EloquentPaymentMethod::factory()->create(['stripe_payment_method_id' => $stripeId]);
        }

        // Act
        $result = $this->repository->findByStripeIds($targetIds);

        // Assert
        $this->assertCount(3, $result);

        $returnedIds = array_map(
            fn($pm) => $pm->stripe_payment_method_id,
            $result
        );

        sort($returnedIds);
        sort($targetIds);

        $this->assertEquals($targetIds, $returnedIds);
    }

    // ==================== DOMAIN OBJECT TESTS ====================

    #[Test]
    public function payment_method_domain_object_has_correct_properties(): void
    {
        $user = User::factory()->create();
        $paymentMethod = EloquentPaymentMethod::factory()->create([
            'user_id' => $user->id,
            'stripe_payment_method_id' => 'pm_test123',
            'brand' => 'visa',
            'last4' => '4242',
            'exp_month' => 12,
            'exp_year' => 2025,
        ]);

        // Act
        $domainPaymentMethod = $this->repository->findById($paymentMethod->id);

        // Assert
        $this->assertNotNull($domainPaymentMethod);
        $this->assertEquals($paymentMethod->id, $domainPaymentMethod->id);
        $this->assertEquals($paymentMethod->stripe_payment_method_id, $domainPaymentMethod->stripe_payment_method_id);
        $this->assertEquals($paymentMethod->user_id, $domainPaymentMethod->user_id);
    }

    // ==================== INTEGRATION TESTS ====================

    #[Test]
    public function complete_payment_method_lifecycle_queries(): void
    {
        // 1. Verificar que no hay métodos de pago inicialmente
        $user = User::factory()->create();
        $initialMethods = $this->repository->getByUserId($user->id);
        $this->assertEmpty($initialMethods);

        // 2. Crear algunos métodos de pago para el usuario
        $stripeIds = [
            'pm_' . fake()->regexify('[A-Za-z0-9]{24}'),
            'pm_' . fake()->regexify('[A-Za-z0-9]{24}'),
            'pm_' . fake()->regexify('[A-Za-z0-9]{24}')
        ];

        $createdMethods = [];
        foreach ($stripeIds as $stripeId) {
            $method = EloquentPaymentMethod::factory()->create([
                'user_id' => $user->id,
                'stripe_payment_method_id' => $stripeId
            ]);
            $createdMethods[] = $method;
        }

        // 3. Buscar todos los métodos de pago del usuario
        $userMethods = $this->repository->getByUserId($user->id);
        $this->assertCount(3, $userMethods);

        // 4. Buscar cada método por stripe_id
        foreach ($stripeIds as $stripeId) {
            $method = $this->repository->findByStripeId($stripeId);
            $this->assertInstanceOf(PaymentMethod::class, $method);
            $this->assertEquals($stripeId, $method->stripe_payment_method_id);
        }

        // 5. Buscar por stripe_ids
        $byStripeIds = $this->repository->findByStripeIds($stripeIds);
        $this->assertCount(3, $byStripeIds);

        // 6. Buscar cada método por ID
        foreach ($createdMethods as $eloquentMethod) {
            $method = $this->repository->findById($eloquentMethod->id);
            $this->assertInstanceOf(PaymentMethod::class, $method);
            $this->assertEquals($eloquentMethod->stripe_payment_method_id, $method->stripe_payment_method_id);
        }

        // 7. Buscar método inexistente
        $nonexistent = $this->repository->findByStripeId('pm_nonexistent');
        $this->assertNull($nonexistent);

        $nonexistentById = $this->repository->findById(999999);
        $this->assertNull($nonexistentById);

        // 8. Buscar por stripe_ids con algunos inexistentes
        $mixedIds = array_merge($stripeIds, ['pm_nonexistent1', 'pm_nonexistent2']);
        $mixedResult = $this->repository->findByStripeIds($mixedIds);
        $this->assertCount(3, $mixedResult);
    }

    #[Test]
    public function repository_methods_independent_of_database_state(): void
    {
        // Este test verifica que cada método funciona independientemente

        // 1. Crear usuario y métodos de pago
        $user = User::factory()->create();

        $method1 = EloquentPaymentMethod::factory()->create([
            'user_id' => $user->id,
            'stripe_payment_method_id' => 'pm_method1'
        ]);

        $method2 = EloquentPaymentMethod::factory()->create([
            'user_id' => $user->id,
            'stripe_payment_method_id' => 'pm_method2'
        ]);

        // Crear métodos para otro usuario
        EloquentPaymentMethod::factory()->create([
            'stripe_payment_method_id' => 'pm_other_user'
        ]);

        // 2. getByUserId
        $userMethods = $this->repository->getByUserId($user->id);
        $this->assertCount(2, $userMethods);

        // 3. findByStripeId específico
        $byStripeIdResult = $this->repository->findByStripeId('pm_method1');
        $this->assertNotNull($byStripeIdResult);
        $this->assertEquals($method1->id, $byStripeIdResult->id);

        // 4. findById específico
        $byIdResult = $this->repository->findById($method2->id);
        $this->assertNotNull($byIdResult);
        $this->assertEquals('pm_method2', $byIdResult->stripe_payment_method_id);

        // 5. findByStripeIds
        $stripeIds = ['pm_method1', 'pm_method2', 'pm_other_user'];
        $byStripeIdsResult = $this->repository->findByStripeIds($stripeIds);
        $this->assertCount(3, $byStripeIdsResult);

        // 6. Verificar que todos los métodos coexisten
        $this->assertCount(2, $this->repository->getByUserId($user->id));
    }

}
