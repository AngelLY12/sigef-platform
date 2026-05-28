<?php

namespace Tests\Unit\Domain\Repositories\Command;

use Tests\Unit\Domain\Repositories\BaseRepositoryTestCase;
use App\Core\Domain\Repositories\Command\User\UserRepInterface;
use Tests\Stubs\Repositories\Command\UserRepositoryStub;
use App\Core\Application\DTO\Request\User\CreateUserDTO;
use App\Core\Application\DTO\Response\User\UserChangedStatusResponse;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\User\UserGender;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Domain\Enum\User\UserBloodType;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Support\Collection;

class UserRepInterfaceTest extends BaseRepositoryTestCase
{
    /**
     * The interface class being implemented
     */
    protected string $interfaceClass = UserRepInterface::class;

    /**
     * Setup the repository instance for testing
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepositoryStub();
    }

    protected function tearDown(): void
    {
        if ($this->repository instanceof UserRepositoryStub) {
            $this->repository->clear();
        }
        parent::tearDown();
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertNotNull($this->repository, 'El repositorio no está inicializado');
        $this->assertImplementsInterface($this->interfaceClass);
    }

    #[Test]
    public function it_has_all_required_methods(): void
    {
        $this->assertNotNull($this->repository, 'El repositorio no está inicializado');

        $reflection = new \ReflectionClass($this->interfaceClass);
        $methods = $reflection->getMethods();

        foreach ($methods as $method) {
            $this->assertMethodExists($method->getName());
        }
    }

    #[Test]
    public function it_can_create_user(): void
    {
        // Arrange
        $dto = new CreateUserDTO(
            name: 'Test',
            last_name: 'User',
            email: 'test@example.com',
            password: 'Password123!',
            phone_number: '+5215512345678',
            curp: 'TEST950101HDFRRN09',
            birthdate: Carbon::create(1995, 1, 1),
            gender: UserGender::HOMBRE,
            status: UserStatus::ACTIVO
        );

        // Act
        $user = $this->repository->create($dto);

        // Assert
        $this->assertInstanceOf(\App\Models\User::class, $user);
        $this->assertEquals('Test', $user->name);
        $this->assertEquals('User', $user->last_name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals(UserStatus::ACTIVO, $user->status);
        $this->assertNotNull($user->id);
    }

    #[Test]
    public function it_can_update_user(): void
    {
        // Arrange
        $updateData = [
            'name' => 'Nombre Actualizado',
            'last_name' => 'Apellido Actualizado',
            'phone_number' => '+5215598765432'
        ];

        // Act
        $updatedUser = $this->repository->update(1, $updateData);

        // Assert
        $this->assertInstanceOf(User::class, $updatedUser);
        $this->assertEquals('Nombre Actualizado', $updatedUser->name);
        $this->assertEquals('Apellido Actualizado', $updatedUser->last_name);
        $this->assertEquals('+5215598765432', $updatedUser->phone_number);
    }

    #[Test]
    public function it_throws_exception_when_updating_non_existing_user(): void
    {
        // Expect
        $this->expectException(\RuntimeException::class);

        // Act
        $this->repository->update(999, ['name' => 'Test']);
    }

    #[Test]
    #[DataProvider('changeStatusProvider')]
    public function it_can_change_user_status(array $userIds, string $status, int $expectedUpdated): void
    {
        // Act
        $result = $this->repository->changeStatus($userIds, $status);

        // Assert
        $this->assertInstanceOf(UserChangedStatusResponse::class, $result);
        $this->assertEquals($status, $result->newStatus);
        $this->assertEquals($expectedUpdated, $result->totalUpdated);
    }

    public static function changeStatusProvider(): array
    {
        return [
            'single_user' => [[1], 'baja-temporal', 1],
            'multiple_users' => [[1, 3, 4], 'baja', 3],
            'no_change_same_status' => [[1], 'activo', 0],
            'empty_array' => [[], 'activo', 0],
            'non_existing_users' => [[999, 1000], 'activo', 0],
            'mixed_existing_non_existing' => [[1, 999, 3], 'eliminado', 2],
        ];
    }

    #[Test]
    public function it_can_insert_multiple_users(): void
    {
        // Arrange
        $usersData = [
            [
                'name' => 'Usuario 1',
                'last_name' => 'Apellido 1',
                'email' => 'usuario1@test.com',
                'password' => 'password1',
                'phone_number' => '+5215511111111',
                'curp' => 'CURP111111HDF11111',
                'status' => 'activo'
            ],
            [
                'name' => 'Usuario 2',
                'last_name' => 'Apellido 2',
                'email' => 'usuario2@test.com',
                'password' => 'password2',
                'phone_number' => '+5215522222222',
                'curp' => 'CURP222222HDF22222',
                'status' => 'activo'
            ]
        ];

        // Act
        $result = $this->repository->insertManyUsers($usersData);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertEquals('usuario1@test.com', $result->first()->email);
        $this->assertEquals('usuario2@test.com', $result->last()->email);
    }

    #[Test]
    public function it_can_insert_single_user(): void
    {
        // Arrange
        $userData = [
            'name' => 'Usuario Individual',
            'last_name' => 'Test',
            'email' => 'individual@test.com',
            'password' => 'password123',
            'phone_number' => '+5215533333333',
            'curp' => 'CURP333333HDF33333',
            'status' => 'activo',
            'birthdate' => '1995-05-05',
            'gender' => 'hombre',
            'blood_type' => 'O+'
        ];

        // Act
        $user = $this->repository->insertSingleUser($userData);

        // Assert
        $this->assertInstanceOf(\App\Models\User::class, $user);
        $this->assertEquals('Usuario Individual', $user->name);
        $this->assertEquals('individual@test.com', $user->email);
        $this->assertEquals(UserStatus::ACTIVO, $user->status);
        $this->assertEquals(UserGender::HOMBRE, $user->gender);
        $this->assertEquals(UserBloodType::O_POSITIVE, $user->blood_type);
    }

    #[Test]
    public function it_can_delete_eliminated_users_after_30_days(): void
    {
        // Arrange
        $initialCount = $this->repository->getUserCount();

        // Act
        $deletedCount = $this->repository->deletionEliminateUsers();

        // Assert
        $this->assertIsInt($deletedCount);
        $this->assertGreaterThanOrEqual(0, $deletedCount);

        // El usuario 2 está eliminado y tiene más de 30 días, debería ser eliminado
        $this->assertEquals(1, $deletedCount);
        $this->assertEquals($initialCount - 1, $this->repository->getUserCount());
    }

    #[Test]
    public function it_can_create_token(): void
    {
        // Act
        $token = $this->repository->createToken(1, 'test-token');

        // Assert
        $this->assertIsString($token);
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes en hex = 64 caracteres
    }

    #[Test]
    public function it_throws_exception_when_creating_token_for_non_existing_user(): void
    {
        // Expect
        $this->expectException(\RuntimeException::class);

        // Act
        $this->repository->createToken(999, 'test-token');
    }

    #[Test]
    public function it_can_assign_role(): void
    {
        // Act
        $result = $this->repository->assignRole(1, 'admin');

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_assigning_role_to_non_existing_user(): void
    {
        // Act
        $result = $this->repository->assignRole(999, 'admin');

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_create_refresh_token(): void
    {
        // Act
        $refreshToken = $this->repository->createRefreshToken(1, 'refresh-token');

        // Assert
        $this->assertIsString($refreshToken);
        $this->assertNotEmpty($refreshToken);
        $this->assertEquals(128, strlen($refreshToken)); // 64 bytes en hex = 128 caracteres
    }

    #[Test]
    public function it_throws_exception_when_creating_refresh_token_for_non_existing_user(): void
    {
        // Expect
        $this->expectException(\RuntimeException::class);

        // Act
        $this->repository->createRefreshToken(999, 'refresh-token');
    }

    #[Test]
    public function it_handles_user_status_enum_conversions(): void
    {
        // Test que podemos pasar strings y se convierten a enums
        $dto = new CreateUserDTO(
            name: 'Enum',
            last_name: 'Test',
            email: 'enum@test.com',
            password: 'Password123!',
            phone_number: '+5215512345678',
            curp: 'ENUM950101HDFRRN09',
            birthdate: new Carbon('2000-01-01'),
            status: UserStatus::BAJA_TEMPORAL
        );

        $user = $this->repository->create($dto);

        $this->assertInstanceOf(UserStatus::class, $user->status);
        $this->assertEquals(UserStatus::BAJA_TEMPORAL, $user->status);
    }

    #[Test]
    public function it_preserves_existing_fields_when_updating(): void
    {
        // Obtener usuario antes de actualizar
        $userBefore = $this->repository->getUser(1);
        $originalEmail = $userBefore->email;
        $originalCurp = $userBefore->curp;

        // Actualizar solo el nombre
        $updatedUser = $this->repository->update(1, ['name' => 'Nuevo Nombre']);

        // Assert
        $this->assertEquals('Nuevo Nombre', $updatedUser->name);
        $this->assertEquals($originalEmail, $updatedUser->email); // Debe preservarse
        $this->assertEquals($originalCurp, $updatedUser->curp);   // Debe preservarse
    }

    #[Test]
    public function user_entity_methods_work_correctly(): void
    {
        // Obtener un usuario
        $user = $this->repository->getUser(1);

        // Test fullName()
        $this->assertEquals('Admin Sistema', $user->fullName());

        // Test isActive()
        $this->assertTrue($user->isActive());

        // Test isDeleted()
        $user2 = $this->repository->getUser(2);
        $this->assertTrue($user2->isDeleted());

        // Test isDisable()
        $user4 = $this->repository->getUser(4);
        $this->assertFalse($user4->isDisable()); // BAJA_TEMPORAL no es BAJA
    }
}
