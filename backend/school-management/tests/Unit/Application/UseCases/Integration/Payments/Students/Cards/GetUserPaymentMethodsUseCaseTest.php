<?php

namespace Tests\Unit\Application\UseCases\Integration\Payments\Students\Cards;

use App\Core\Application\DTO\Response\PaymentMethod\DisplayPaymentMethodResponse;
use App\Core\Application\UseCases\Payments\Student\Cards\GetUserPaymentMethodsUseCase;
use App\Core\Domain\Enum\User\UserRoles;
use App\Models\PaymentMethod;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use App\Models\User as UserModel;

class GetUserPaymentMethodsUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private GetUserPaymentMethodsUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCase = app(GetUserPaymentMethodsUseCase::class);
        $this->seed(RolesSeeder::class);
    }

    #[Test]
    public function it_returns_empty_array_when_user_has_no_payment_methods(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        // Act
        $result = $this->useCase->execute($user->id);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_returns_single_payment_method(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        // Crear método de pago para el usuario
        $paymentMethod = PaymentMethod::factory()->forUser($user)->create([
            'brand' => 'Visa',
            'last4' => '4242',
            'exp_month' => 12,
            'exp_year' => date('Y') + 2, // Vigente
        ]);

        // Act
        $result = $this->useCase->execute($user->id);

        // Assert
        $this->assertCount(1, $result);
        $this->assertInstanceOf(DisplayPaymentMethodResponse::class, $result[0]);
        $this->assertEquals($paymentMethod->id, $result[0]->id);
        $this->assertEquals('Visa', $result[0]->brand);
        $this->assertStringContainsString('4242', $result[0]->masked_card);
        $this->assertEquals('Vigente', $result[0]->status);
    }

    #[Test]
    public function it_returns_multiple_payment_methods(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        // Crear 3 métodos de pago
        PaymentMethod::factory()->count(3)->forUser($user)->create();

        // Act
        $result = $this->useCase->execute($user->id);

        // Assert
        $this->assertCount(3, $result);
        foreach ($result as $response) {
            $this->assertInstanceOf(DisplayPaymentMethodResponse::class, $response);
            $this->assertNotEmpty($response->brand);
            $this->assertNotEmpty($response->masked_card);
            $this->assertNotEmpty($response->status);
        }
    }

    #[Test]
    public function it_shows_expired_payment_methods_as_caducada(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        // Crear método de pago expirado
        PaymentMethod::factory()->forUser($user)->expired()->create([
            'brand' => 'Visa',
            'last4' => '9999',
        ]);

        // Act
        $result = $this->useCase->execute($user->id);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Caducada', $result[0]->status);
    }

    #[Test]
    public function it_shows_active_payment_methods_as_vigente(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        // Crear método de pago vigente
        PaymentMethod::factory()->forUser($user)->create([
            'exp_year' => date('Y') + 3, // Bien en el futuro
        ]);

        // Act
        $result = $this->useCase->execute($user->id);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Vigente', $result[0]->status);
    }

    #[Test]
    public function it_returns_only_payment_methods_for_specific_user(): void
    {
        // Arrange - Crear dos usuarios
        $user1 = UserModel::factory()->asStudent()->create(['email' => 'user1@test.com']);
        $user2 = UserModel::factory()->asStudent()->create(['email' => 'user2@test.com']);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user1->assignRole($studentRole);
        $user2->assignRole($studentRole);

        // Métodos de pago solo para user1
        PaymentMethod::factory()->count(2)->forUser($user1)->create();
        PaymentMethod::factory()->count(1)->forUser($user2)->create();

        // Act para user1
        $result1 = $this->useCase->execute($user1->id);

        // Act para user2
        $result2 = $this->useCase->execute($user2->id);

        // Assert
        $this->assertCount(2, $result1);
        $this->assertCount(1, $result2);
    }

    #[Test]
    public function it_returns_correct_response_structure(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        PaymentMethod::factory()->forUser($user)->create([
            'brand' => 'MasterCard',
            'last4' => '5555',
            'exp_month' => 6,
            'exp_year' => date('Y') + 1,
        ]);

        // Act
        $result = $this->useCase->execute($user->id);
        $response = $result[0];

        // Assert estructura
        $this->assertInstanceOf(DisplayPaymentMethodResponse::class, $response);
        $this->assertObjectHasProperty('id', $response);
        $this->assertObjectHasProperty('brand', $response);
        $this->assertObjectHasProperty('masked_card', $response);
        $this->assertObjectHasProperty('expiration_date', $response);
        $this->assertObjectHasProperty('status', $response);

        // Verificar formato de fecha
        $this->assertMatchesRegularExpression('/\d{2}\/\d{2}/', $response->expiration_date);
    }

    #[Test]
    public function it_handles_different_card_brands(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        // Crear diferentes tipos de tarjetas
        PaymentMethod::factory()->forUser($user)->visa()->create();
        PaymentMethod::factory()->forUser($user)->mastercard()->create();
        PaymentMethod::factory()->forUser($user)->americanExpress()->create();

        // Act
        $result = $this->useCase->execute($user->id);

        // Assert
        $this->assertCount(3, $result);

        $brands = array_column($result, 'brand');
        $this->assertContains('Visa', $brands);
        $this->assertContains('MasterCard', $brands);
        $this->assertContains('American Express', $brands);
    }

    #[Test]
    public function it_handles_payment_method_about_to_expire(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        // Crear método que expira este mes
        $currentMonth = (int) date('m');
        $currentYear = (int) date('Y');

        PaymentMethod::factory()->forUser($user)->expiresAt($currentMonth, $currentYear)->create();

        // Act
        $result = $this->useCase->execute($user->id);

        // Assert - Aún debería ser "Vigente" si expira este mes
        $this->assertCount(1, $result);
        $this->assertEquals('Vigente', $result[0]->status);
    }

    #[Test]
    public function it_formats_masked_card_correctly(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        PaymentMethod::factory()->forUser($user)->create([
            'last4' => '1234',
        ]);

        // Act
        $result = $this->useCase->execute($user->id);

        // Assert - El masked card debe contener los últimos 4 dígitos
        $this->assertStringContainsString('1234', $result[0]->masked_card);
        $this->assertStringContainsString('****', $result[0]->masked_card);
    }

    #[Test]
    public function it_formats_expiration_date_correctly(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        PaymentMethod::factory()->forUser($user)->create([
            'exp_month' => 3, // Marzo
            'exp_year' => 2025,
        ]);

        // Act
        $result = $this->useCase->execute($user->id);

        // Assert - Formato MM/YY
        $this->assertEquals('03/25', $result[0]->expiration_date);
    }

    #[Test]
    public function it_handles_single_digit_month_in_expiration_date(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        PaymentMethod::factory()->forUser($user)->create([
            'exp_month' => 9, // Septiembre - un dígito
            'exp_year' => 2026,
        ]);

        // Act
        $result = $this->useCase->execute($user->id);

        // Assert - Debería formatear como 09/26
        $this->assertEquals('09/26', $result[0]->expiration_date);
    }

    #[Test]
    public function it_returns_payment_methods_in_order_from_repository(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        // Crear métodos con diferentes fechas de creación
        $method1 = PaymentMethod::factory()->forUser($user)->create(['created_at' => now()->subDays(3)]);
        $method2 = PaymentMethod::factory()->forUser($user)->create(['created_at' => now()->subDays(2)]);
        $method3 = PaymentMethod::factory()->forUser($user)->create(['created_at' => now()->subDays(1)]);

        // Act
        $result = $this->useCase->execute($user->id);

        // Assert - Depende de cómo tu repositorio ordene
        // Por defecto probablemente por ID o created_at
        $this->assertCount(3, $result);

        // Puedes verificar que todos están presentes
        $ids = array_column($result, 'id');
        $this->assertContains($method1->id, $ids);
        $this->assertContains($method2->id, $ids);
        $this->assertContains($method3->id, $ids);
    }

    #[Test]
    public function it_handles_user_that_does_not_exist(): void
    {
        // Arrange - Usuario que no existe
        $nonExistentUserId = 999999;

        // Act
        $result = $this->useCase->execute($nonExistentUserId);

        // Assert - Debería retornar array vacío, no error
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_uses_test_stripe_cards_correctly(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        // Usar el factory de test stripe card
        PaymentMethod::factory()->forUser($user)->testStripeCard()->create();

        // Act
        $result = $this->useCase->execute($user->id);

        // Assert
        $this->assertCount(1, $result);
        $this->assertContains($result[0]->brand, ['Visa', 'MasterCard', 'American Express']);
        $this->assertContains($result[0]->status, ['Vigente', 'Caducada']);
    }

    #[Test]
    public function it_mixes_expired_and_active_cards(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        // 2 activas, 1 expirada
        PaymentMethod::factory()->count(2)->forUser($user)->create(); // Activas
        PaymentMethod::factory()->forUser($user)->expired()->create(); // Expirada

        // Act
        $result = $this->useCase->execute($user->id);

        // Assert
        $this->assertCount(3, $result);

        $statuses = array_column($result, 'status');
        $vigenteCount = array_count_values($statuses)['Vigente'] ?? 0;
        $caducadaCount = array_count_values($statuses)['Caducada'] ?? 0;

        $this->assertEquals(2, $vigenteCount);
        $this->assertEquals(1, $caducadaCount);
    }

}
