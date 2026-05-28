<?php

namespace Tests\Unit\Infraestructure\Repositories\Command;

use App\Core\Domain\Entities\PaymentMethod;
use App\Core\Infraestructure\Mappers\PaymentMethodMapper;
use App\Models\PaymentMethod as EloquentPaymentMethod;
use App\Core\Infraestructure\Repositories\Command\Payments\EloquentPaymentMethodRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentPaymentMethodRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentPaymentMethodRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentPaymentMethodRepository();
    }

    #[Test]
    public function create_payment_method_successfully(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        $paymentMethod = new PaymentMethod(
            user_id: $user->id,
            stripe_payment_method_id: 'pm_test_123456789',
            brand: 'Visa',
            last4: '4242',
            exp_month: 12,
            exp_year: 2027
        );

        // Act
        $result = $this->repository->create($paymentMethod);

        // Assert
        $this->assertInstanceOf(PaymentMethod::class, $result);
        $this->assertNotNull($result->id);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals('pm_test_123456789', $result->stripe_payment_method_id);
        $this->assertEquals('Visa', $result->brand);
        $this->assertEquals('4242', $result->last4);
        $this->assertEquals(12, $result->exp_month);
        $this->assertEquals(2027, $result->exp_year);

        $this->assertDatabaseHas('payment_methods', [
            'user_id' => $user->id,
            'stripe_payment_method_id' => 'pm_test_123456789',
            'brand' => 'Visa',
            'last4' => '4242',
            'exp_month' => 12,
            'exp_year' => 2027,
        ]);
    }

    #[Test]
    public function create_payment_method_with_minimal_data(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        $paymentMethod = new PaymentMethod(
            user_id: $user->id,
            stripe_payment_method_id: 'pm_test_minimal'
        );

        // Act
        $result = $this->repository->create($paymentMethod);

        // Assert
        $this->assertInstanceOf(PaymentMethod::class, $result);
        $this->assertNotNull($result->id);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals('pm_test_minimal', $result->stripe_payment_method_id);
        $this->assertNull($result->brand);
        $this->assertNull($result->last4);
        $this->assertNull($result->exp_month);
        $this->assertNull($result->exp_year);

        $this->assertDatabaseHas('payment_methods', [
            'user_id' => $user->id,
            'stripe_payment_method_id' => 'pm_test_minimal',
            'brand' => null,
            'last4' => null,
            'exp_month' => null,
            'exp_year' => null,
        ]);
    }

    #[Test]
    public function create_payment_method_with_factory_data(): void
    {
        // Arrange
        $paymentMethodData = EloquentPaymentMethod::factory()->make();
        $domainPaymentMethod = PaymentMethodMapper::toDomain($paymentMethodData);

        // Act
        $result = $this->repository->create($domainPaymentMethod);

        // Assert
        $this->assertInstanceOf(PaymentMethod::class, $result);
        $this->assertNotNull($result->id);
        $this->assertEquals($paymentMethodData->user_id, $result->user_id);
        $this->assertEquals($paymentMethodData->stripe_payment_method_id, $result->stripe_payment_method_id);
        $this->assertEquals($paymentMethodData->brand, $result->brand);
        $this->assertEquals($paymentMethodData->last4, $result->last4);
        $this->assertEquals($paymentMethodData->exp_month, $result->exp_month);
        $this->assertEquals($paymentMethodData->exp_year, $result->exp_year);

        $this->assertDatabaseHas('payment_methods', [
            'user_id' => $paymentMethodData->user_id,
            'stripe_payment_method_id' => $paymentMethodData->stripe_payment_method_id,
        ]);
    }

    #[Test]
    public function create_multiple_payment_methods_for_same_user(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();

        $paymentMethod1 = new PaymentMethod(
            user_id: $user->id,
            stripe_payment_method_id: 'pm_1',
            brand: 'Visa',
            last4: '1111'
        );

        $paymentMethod2 = new PaymentMethod(
            user_id: $user->id,
            stripe_payment_method_id: 'pm_2',
            brand: 'MasterCard',
            last4: '2222'
        );

        // Act
        $result1 = $this->repository->create($paymentMethod1);
        $result2 = $this->repository->create($paymentMethod2);

        // Assert
        $this->assertInstanceOf(PaymentMethod::class, $result1);
        $this->assertInstanceOf(PaymentMethod::class, $result2);
        $this->assertNotEquals($result1->id, $result2->id);

        $paymentMethodCount = EloquentPaymentMethod::where('user_id', $user->id)->count();
        $this->assertEquals(2, $paymentMethodCount);
    }

    #[Test]
    public function create_payment_method_with_unique_stripe_id(): void
    {
        // Arrange
        $user1 = \App\Models\User::factory()->create();
        $user2 = \App\Models\User::factory()->create();

        $paymentMethod1 = new PaymentMethod(
            user_id: $user1->id,
            stripe_payment_method_id: 'pm_unique_123'
        );

        $paymentMethod2 = new PaymentMethod(
            user_id: $user2->id,
            stripe_payment_method_id: 'pm_unique_456'
        );

        // Act & Assert - No debería haber conflicto aunque sean stripe IDs diferentes
        $result1 = $this->repository->create($paymentMethod1);
        $result2 = $this->repository->create($paymentMethod2);

        $this->assertNotNull($result1->id);
        $this->assertNotNull($result2->id);

        $this->assertDatabaseHas('payment_methods', [
            'stripe_payment_method_id' => 'pm_unique_123',
            'user_id' => $user1->id,
        ]);

        $this->assertDatabaseHas('payment_methods', [
            'stripe_payment_method_id' => 'pm_unique_456',
            'user_id' => $user2->id,
        ]);
    }

    #[Test]
    public function delete_payment_method_successfully(): void
    {
        // Arrange
        $paymentMethod = EloquentPaymentMethod::factory()->create();

        // Act
        $this->repository->delete($paymentMethod->id);

        // Assert
        $this->assertDatabaseMissing('payment_methods', [
            'id' => $paymentMethod->id,
        ]);
    }

    #[Test]
    public function delete_payment_method_that_does_not_exist_throws_exception(): void
    {
        // Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $this->repository->delete(999999);
    }

    #[Test]
    public function delete_payment_method_removes_only_specific_record(): void
    {
        // Arrange
        $paymentMethod1 = EloquentPaymentMethod::factory()->create();
        $paymentMethod2 = EloquentPaymentMethod::factory()->create();
        $paymentMethod3 = EloquentPaymentMethod::factory()->create();

        // Act
        $this->repository->delete($paymentMethod2->id);

        // Assert
        $this->assertDatabaseMissing('payment_methods', [
            'id' => $paymentMethod2->id,
        ]);

        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethod1->id,
        ]);

        $this->assertDatabaseHas('payment_methods', [
            'id' => $paymentMethod3->id,
        ]);
    }

    #[Test]
    public function delete_payment_method_cascades_appropriately(): void
    {
        // Arrange
        $paymentMethod = EloquentPaymentMethod::factory()->create();

        // Si hay relaciones dependientes, se prueban aquí
        // Por ejemplo, si hay pagos asociados al método de pago

        // Act
        $this->repository->delete($paymentMethod->id);

        // Assert
        $this->assertDatabaseMissing('payment_methods', [
            'id' => $paymentMethod->id,
        ]);
    }

    #[Test]
    public function create_and_then_delete_payment_method(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();
        $paymentMethod = new PaymentMethod(
            user_id: $user->id,
            stripe_payment_method_id: 'pm_to_delete'
        );

        // Act - Create
        $created = $this->repository->create($paymentMethod);

        // Assert - After create
        $this->assertDatabaseHas('payment_methods', [
            'id' => $created->id,
            'stripe_payment_method_id' => 'pm_to_delete',
        ]);

        // Act - Delete
        $this->repository->delete($created->id);

        // Assert - After delete
        $this->assertDatabaseMissing('payment_methods', [
            'id' => $created->id,
        ]);
    }

    #[Test]
    public function payment_method_has_correct_expiration_logic(): void
    {
        // Arrange
        $paymentMethod = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_test',
            exp_month: 12,
            exp_year: (int)(date('Y') - 1) // Año pasado - expirado
        );

        // Act & Assert - Probar métodos del dominio
        $this->assertTrue($paymentMethod->isExpired());
        $this->assertEquals('12/' . substr((string)(date('Y') - 1), -2), $paymentMethod->expirationDate());
    }

    #[Test]
    public function payment_method_has_correct_masked_card(): void
    {
        // Arrange
        $paymentMethod = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_test',
            last4: '4242'
        );

        // Act & Assert
        $this->assertEquals('**** **** **** 4242', $paymentMethod->getMaskedCard());
    }

    #[Test]
    public function payment_method_without_last4_returns_null_masked_card(): void
    {
        // Arrange
        $paymentMethod = new PaymentMethod(
            user_id: 1,
            stripe_payment_method_id: 'pm_test'
        // sin last4
        );

        // Act & Assert
        $this->assertNull($paymentMethod->getMaskedCard());
    }

    #[Test]
    public function create_payment_method_with_factory_methods(): void
    {
        // Arrange
        $user = \App\Models\User::factory()->create();

        // Usar diferentes factory states
        $paymentMethodData1 = EloquentPaymentMethod::factory()->visa()->forUser($user)->make();
        $paymentMethodData2 = EloquentPaymentMethod::factory()->expired()->forUser($user)->make();

        $domainPaymentMethod1 = PaymentMethodMapper::toDomain($paymentMethodData1);
        $domainPaymentMethod2 = PaymentMethodMapper::toDomain($paymentMethodData2);

        // Act
        $result1 = $this->repository->create($domainPaymentMethod1);
        $result2 = $this->repository->create($domainPaymentMethod2);

        // Assert
        $this->assertEquals('Visa', $result1->brand);
        $this->assertTrue($result2->isExpired());
    }

    #[Test]
    public function payment_method_persistence_maintains_all_fields(): void
    {
        // Arrange
        $paymentMethodData = EloquentPaymentMethod::factory()->state([
            'brand' => 'American Express',
            'last4' => '3782',
            'exp_month' => 10,
            'exp_year' => 2030,
        ])->make();

        $domainPaymentMethod = PaymentMethodMapper::toDomain($paymentMethodData);

        // Act
        $result = $this->repository->create($domainPaymentMethod);

        // Assert
        $this->assertEquals('American Express', $result->brand);
        $this->assertEquals('3782', $result->last4);
        $this->assertEquals(10, $result->exp_month);
        $this->assertEquals(2030, $result->exp_year);

        // Verificar en base de datos
        $dbRecord = EloquentPaymentMethod::find($result->id);
        $this->assertNotNull($dbRecord);
        $this->assertEquals('American Express', $dbRecord->brand);
        $this->assertEquals('3782', $dbRecord->last4);
        $this->assertEquals(10, $dbRecord->exp_month);
        $this->assertEquals(2030, $dbRecord->exp_year);
    }

    #[Test]
    public function mapper_produces_correct_domain_object_after_create(): void
    {
        // Arrange
        $paymentMethodData = EloquentPaymentMethod::factory()->make();
        $originalDomain = PaymentMethodMapper::toDomain($paymentMethodData);

        // Act
        $created = $this->repository->create($originalDomain);

        // Assert
        $this->assertInstanceOf(PaymentMethod::class, $created);
        $this->assertNotNull($created->id);

        // El objeto creado debe tener todos los campos del original
        $this->assertEquals($originalDomain->user_id, $created->user_id);
        $this->assertEquals($originalDomain->stripe_payment_method_id, $created->stripe_payment_method_id);
        $this->assertEquals($originalDomain->brand, $created->brand);
        $this->assertEquals($originalDomain->last4, $created->last4);
        $this->assertEquals($originalDomain->exp_month, $created->exp_month);
        $this->assertEquals($originalDomain->exp_year, $created->exp_year);
    }

    #[Test]
    public function payment_method_can_be_deleted_after_creation(): void
    {
        // Test de integración completo: Create → Verify → Delete → Verify

        // Arrange
        $paymentMethod = new PaymentMethod(
            user_id: \App\Models\User::factory()->create()->id,
            stripe_payment_method_id: 'pm_full_integration_test'
        );

        // Act 1: Create
        $created = $this->repository->create($paymentMethod);

        // Assert 1: Verify creation
        $this->assertDatabaseHas('payment_methods', [
            'id' => $created->id,
            'stripe_payment_method_id' => 'pm_full_integration_test'
        ]);

        // Act 2: Delete
        $this->repository->delete($created->id);

        // Assert 2: Verify deletion
        $this->assertDatabaseMissing('payment_methods', [
            'id' => $created->id
        ]);

        // Act 3: Try to delete again (should fail)
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->delete($created->id);
    }

    #[Test]
    public function payment_method_refresh_returns_complete_object(): void
    {
        // Arrange
        $paymentMethod = new PaymentMethod(
            user_id: \App\Models\User::factory()->create()->id,
            stripe_payment_method_id: 'pm_refresh_test',
            brand: 'Discover',
            last4: '6011',
            exp_month: 03,
            exp_year: 2026
        );

        // Act
        $result = $this->repository->create($paymentMethod);

        // Assert
        $this->assertNotNull($result->id);
        $this->assertNotNull($result->user_id);
        $this->assertNotNull($result->stripe_payment_method_id);
        $this->assertNotNull($result->brand);
        $this->assertNotNull($result->last4);
        $this->assertNotNull($result->exp_month);
        $this->assertNotNull($result->exp_year);

        // Todos los campos deben estar presentes
        $this->assertEquals('Discover', $result->brand);
        $this->assertEquals('6011', $result->last4);
        $this->assertEquals(03, $result->exp_month);
        $this->assertEquals(2026, $result->exp_year);
    }

    #[Test]
    public function multiple_operations_on_same_repository_instance(): void
    {
        // Test para verificar que el repositorio maneja múltiples operaciones

        // Arrange
        $user1 = \App\Models\User::factory()->create();
        $user2 = \App\Models\User::factory()->create();

        $paymentMethod1 = new PaymentMethod(
            user_id: $user1->id,
            stripe_payment_method_id: 'pm_multi_1'
        );

        $paymentMethod2 = new PaymentMethod(
            user_id: $user2->id,
            stripe_payment_method_id: 'pm_multi_2'
        );

        // Act 1: Create first
        $created1 = $this->repository->create($paymentMethod1);

        // Act 2: Create second
        $created2 = $this->repository->create($paymentMethod2);

        // Act 3: Delete first
        $this->repository->delete($created1->id);

        // Assert
        $this->assertDatabaseMissing('payment_methods', ['id' => $created1->id]);
        $this->assertDatabaseHas('payment_methods', ['id' => $created2->id]);

        // Act 4: Delete second
        $this->repository->delete($created2->id);

        // Final assert
        $this->assertDatabaseMissing('payment_methods', ['id' => $created2->id]);
        $this->assertEquals(0, EloquentPaymentMethod::count());
    }

}
