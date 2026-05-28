<?php

namespace Tests\Unit\Application\UseCases\Integration\Payments\Students\Cards;

use App\Core\Application\DTO\Response\PaymentMethod\SetupCardResponse;
use App\Core\Application\UseCases\Payments\Student\Cards\SetupCardUseCase;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Infraestructure\Mappers\UserMapper;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use App\Models\User as UserModel;

class SetupCardUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private SetupCardUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.frontend_url' => 'https://localhost:3000']);

        // Verificar que estamos en entorno testing
        $this->assertEquals('testing', config('app.env'),
            'Tests must run in testing environment');

        $this->useCase = app(SetupCardUseCase::class);
        $this->seed(RolesSeeder::class);
    }


    #[Test]
    public function it_creates_stripe_customer_and_setup_session(): void
    {
        // Skip si no hay configuración válida
        $secret = config('services.stripe.secret');
        if (empty($secret)) {
            $this->markTestSkipped('Stripe not configured in .env.testing');
        }

        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'name' => 'Test',
            'last_name' => 'User',
            'email' => 'test_' . uniqid() . '_' . time() . '@example.com',
            'stripe_customer_id' => null
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertInstanceOf(SetupCardResponse::class, $result);
        $this->assertNotEmpty($result->id);
        $this->assertNotEmpty($result->url);
        $this->assertStringContainsString('https://checkout.stripe.com/', $result->url);

        // Verificar persistencia
        $user->refresh();
        $this->assertNotNull($user->stripe_customer_id);
        $this->assertStringStartsWith('cus_', $user->stripe_customer_id);

        // Verificar en base de datos
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'stripe_customer_id' => $user->stripe_customer_id
        ]);
    }

    #[Test]
    public function it_handles_empty_string_customer_id(): void
    {
        $user = UserModel::factory()->asStudent()->create([
            'email' => 'empty_' . uniqid() . '@example.com',
            'stripe_customer_id' => '' // String vacío
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert - Debería crear nuevo customer ("" se trata como null)
        $user->refresh();
        $this->assertNotEmpty($user->stripe_customer_id);
        $this->assertNotEquals('', $user->stripe_customer_id);
    }

    #[Test]
    public function it_creates_different_sessions_for_same_user(): void
    {
        $user = UserModel::factory()->asStudent()->create([
            'email' => 'multi_' . uniqid() . '@example.com',
            'stripe_customer_id' => null
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Primera llamada
        $result1 = $this->useCase->execute($userEntity);
        $customerIdAfterFirst = $user->fresh()->stripe_customer_id;

        // Segunda llamada
        $result2 = $this->useCase->execute($userEntity);
        $customerIdAfterSecond = $user->fresh()->stripe_customer_id;

        // Assert
        $this->assertEquals($customerIdAfterFirst, $customerIdAfterSecond); // Mismo customer
        $this->assertNotEquals($result1->id, $result2->id); // Diferentes sessions
    }

    #[Test]
    public function it_returns_correct_response_structure(): void
    {
        $user = UserModel::factory()->asStudent()->create([
            'email' => 'struct_' . uniqid() . '@example.com',
            'stripe_customer_id' => null
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert - Estructura EXACTA
        $this->assertInstanceOf(SetupCardResponse::class, $result);
        $this->assertObjectHasProperty('id', $result);
        $this->assertObjectHasProperty('url', $result);
        $this->assertStringStartsWith('cs_test_', $result->id); // Checkout Session ID
    }

    #[Test]
    public function it_persists_customer_id_in_database(): void
    {
        $user = UserModel::factory()->asStudent()->create([
            'email' => 'persist_' . uniqid() . '@example.com',
            'stripe_customer_id' => null
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Act
        $this->useCase->execute($userEntity);

        // Assert - Directo en BD
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            ['stripe_customer_id', '!=', null],
            ['stripe_customer_id', '!=', ''],
        ]);
    }

}
