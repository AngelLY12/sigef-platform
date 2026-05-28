<?php

namespace Tests\Unit\Infraestructure\Repositories\Command;

use App\Core\Application\DTO\Request\User\CreateUserDTO;
use App\Core\Application\DTO\Response\User\UserChangedStatusResponse;
use App\Core\Domain\Entities\User;
use App\Models\User as EloquentUser;
use App\Core\Domain\Enum\User\UserBloodType;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Infraestructure\Repositories\Command\User\EloquentUserRepository;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentUserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        \Spatie\Permission\Models\Role::create(['name' => UserRoles::STUDENT->value, 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => UserRoles::ADMIN->value, 'guard_name' => 'web']);
        \Spatie\Permission\Models\Role::create(['name' => UserRoles::UNVERIFIED->value, 'guard_name' => 'web']);

        $this->repository = new EloquentUserRepository();
    }

    // ==================== CREATE TESTS ====================

    #[Test]
    public function create_user_successfully(): void
    {
        // Arrange
        $userDTO = new CreateUserDTO(
            name: 'Juan',
            last_name: 'Pérez',
            email: 'juan@example.com',
            password: 'password123',
            phone_number: '5512345678',
            curp: 'PEMJ900101HDFRRN09',
            birthdate: new Carbon('1990-01-01'),
            gender: \App\Core\Domain\Enum\User\UserGender::HOMBRE,
            address: [
                'street' => 'Calle 123',
                'city' => 'Ciudad de México',
                'state' => 'CDMX',
                'postal_code' => '12345',
                'country' => 'México'
            ],
            blood_type: \App\Core\Domain\Enum\User\UserBloodType::A_POSITIVE,
            registration_date: new Carbon('2023-01-01'),
            status: UserStatus::ACTIVO
        );

        // Act
        $result = $this->repository->create($userDTO);

        // Assert
        $this->assertInstanceOf(\App\Models\User::class, $result);
        $this->assertNotNull($result->id);
        $this->assertEquals('Juan', $result->name);
        $this->assertEquals('juan@example.com', $result->email);
        $this->assertEquals(UserStatus::ACTIVO, $result->status);

        $this->assertDatabaseHas('users', [
            'email' => 'juan@example.com',
            'name' => 'Juan',
            'last_name' => 'Pérez'
        ]);
    }

    #[Test]
    public function create_user_with_minimal_data(): void
    {
        // Arrange
        $userDTO = new CreateUserDTO(
            name: 'Ana',
            last_name: 'López',
            email: 'ana@example.com',
            password: 'password123',
            phone_number: '5598765432',
            curp: 'LOAA950515MDFLPN01',
            birthdate: new Carbon('1995-05-15'),
            gender: \App\Core\Domain\Enum\User\UserGender::MUJER,
            address: [],
            status: UserStatus::ACTIVO
        );

        // Act
        $result = $this->repository->create($userDTO);

        // Assert
        $this->assertInstanceOf(\App\Models\User::class, $result);
        $this->assertEquals('Ana', $result->name);
        $this->assertEquals('ana@example.com', $result->email);
        $this->assertNull($result->blood_type);
        $this->assertNotNull($result->registration_date);
    }

    // ==================== UPDATE TESTS ====================

    #[Test]
    public function update_user_successfully(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();
        $newName = 'Nombre Actualizado';
        $newEmail = 'actualizado@example.com';

        // Act
        $result = $this->repository->update($user->id, [
            'name' => $newName,
            'email' => $newEmail
        ]);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($newName, $result->name);
        $this->assertEquals($newEmail, $result->email);

        $user->refresh();
        $this->assertEquals($newName, $user->name);
        $this->assertEquals($newEmail, $user->email);
    }

    #[Test]
    public function update_user_partial_fields(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create(['name' => 'Original']);
        $originalEmail = $user->email;

        // Act
        $result = $this->repository->update($user->id, [
            'name' => 'Nuevo Nombre'
            // No cambiar email
        ]);

        // Assert
        $this->assertEquals('Nuevo Nombre', $result->name);
        $this->assertEquals($originalEmail, $result->email);
    }

    #[Test]
    public function update_user_throws_exception_when_not_found(): void
    {
        // Arrange
        $nonExistentId = 999999;

        // Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $this->repository->update($nonExistentId, ['name' => 'Test']);
    }

    // ==================== CREATE TOKEN TESTS ====================

    #[Test]
    public function create_token_successfully(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();
        $tokenName = 'Test Token';

        // Act
        $token = $this->repository->createToken($user->id, $tokenName);

        // Assert
        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        // Verificar que el token existe en la base de datos
        $accessToken = PersonalAccessToken::findToken($token);
        $this->assertNotNull($accessToken);
        $this->assertEquals($tokenName, $accessToken->name);
        $this->assertEquals($user->id, $accessToken->tokenable_id);
    }

    #[Test]
    public function create_token_expires_in_30_minutes(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();
        $now = now();

        // Act
        $token = $this->repository->createToken($user->id, 'Test');

        // Assert
        $accessToken = PersonalAccessToken::findToken($token);
        $this->assertNotNull($accessToken->expires_at);

        $expectedExpiration = $now->addMinutes(30);
        $this->assertEqualsWithDelta(
            $expectedExpiration->timestamp,
            $accessToken->expires_at->timestamp,
            5 // Tolerancia de 5 segundos
        );
    }

    // ==================== CREATE REFRESH TOKEN TESTS ====================

    #[Test]
    public function create_refresh_token_successfully(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();
        $user->assignRole(UserRoles::STUDENT->value);
        $tokenName = 'Refresh Token';

        // Act
        $refreshToken = $this->repository->createRefreshToken($user->id, $tokenName);

        // Assert
        $this->assertIsString($refreshToken);
        $this->assertNotEmpty($refreshToken);
        $this->assertEquals(128, strlen($refreshToken)); // 64 bytes en hex = 128 caracteres

        // Verificar que se guardó el hash en la base de datos
        $hashedToken = hash('sha256', $refreshToken);
        $this->assertDatabaseHas('refresh_tokens', [
            'user_id' => $user->id,
            'token' => $hashedToken,
            'revoked' => false
        ]);
    }

    #[Test]
    public function create_refresh_token_with_admin_role_has_longer_expiration(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();
        $user->assignRole(UserRoles::ADMIN->value);

        config(['refresh-token.expiration_time_by_role.admin' => 1440]); // 24 horas
        config(['refresh-token.default_refresh_ttl' => 60]); // 1 hora por defecto

        // Act
        $refreshToken = $this->repository->createRefreshToken($user->id, 'Admin Token');

        // Assert
        $hashedToken = hash('sha256', $refreshToken);
        $tokenRecord = DB::table('refresh_tokens')
            ->where('token', $hashedToken)
            ->first();

        $this->assertNotNull($tokenRecord);
        $expectedExpiration = now()->addMinutes(1440);
        $actualExpiration = Carbon::parse($tokenRecord->expires_at);

        $this->assertEqualsWithDelta(
            $expectedExpiration->timestamp,
            $actualExpiration->timestamp,
            5
        );
    }

    // ==================== ASSIGN ROLE TESTS ====================

    #[Test]
    public function assign_role_successfully(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();

        // Act
        $result = $this->repository->assignRole($user->id, UserRoles::STUDENT->value);

        // Assert
        $this->assertTrue($result);
        $user->refresh();
        $this->assertTrue($user->hasRole(UserRoles::STUDENT->value));
    }

    #[Test]
    public function assign_role_removes_unverified_role(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();
        $user->assignRole(UserRoles::UNVERIFIED->value);
        $this->assertTrue($user->hasRole(UserRoles::UNVERIFIED->value));

        // Act
        $this->repository->assignRole($user->id, UserRoles::STUDENT->value);

        // Assert
        $user->refresh();
        $this->assertTrue($user->hasRole(UserRoles::STUDENT->value));
        $this->assertFalse($user->hasRole(UserRoles::UNVERIFIED->value));
    }

    #[Test]
    public function assign_role_returns_false_for_nonexistent_user(): void
    {
        // Act
        $result = $this->repository->assignRole(999999, UserRoles::STUDENT->value);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function assign_role_keeps_unverified_if_assigned_same_role(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();
        $user->assignRole(UserRoles::UNVERIFIED->value);

        // Act - Asignar el mismo rol UNVERIFIED
        $this->repository->assignRole($user->id, UserRoles::UNVERIFIED->value);

        // Assert - Debe mantener el rol UNVERIFIED
        $user->refresh();
        $this->assertTrue($user->hasRole(UserRoles::UNVERIFIED->value));
    }

    // ==================== INSERT MANY USERS TESTS ====================

    #[Test]
    public function insert_many_users_successfully(): void
    {
        // Arrange
        $usersData = [];
        for ($i = 1; $i <= 5; $i++) {
            $usersData[] = [
                'name' => "User $i",
                'last_name' => "Last $i",
                'email' => "user$i@example.com",
                'phone_number' => '551234567' . $i,
                'birthdate' => new Carbon('1990-01-0'. $i),
                'gender' => \App\Core\Domain\Enum\User\UserGender::HOMBRE,
                'curp' => 'TEST' . str_pad($i, 2, '0', STR_PAD_LEFT) . '0101HDFRRN09',
                'password' => bcrypt('password'),
                'status' => UserStatus::ACTIVO,
                'registration_date' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Act
        $result = $this->repository->insertManyUsers($usersData);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(5, $result);

        foreach ($result as $user) {
            $this->assertInstanceOf(EloquentUser::class, $user);
        }

        // Verificar que todos los usuarios están en la base de datos
        foreach ($usersData as $userData) {
            $this->assertDatabaseHas('users', [
                'email' => $userData['email'],
                'name' => $userData['name']
            ]);
        }
    }

    #[Test]
    public function insert_many_users_returns_empty_collection_for_empty_array(): void
    {
        // Arrange
        $emptyArray = [];

        // Act
        $result = $this->repository->insertManyUsers($emptyArray);

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }

    // ==================== INSERT SINGLE USER TESTS ====================

    #[Test]
    public function insert_single_user_successfully(): void
    {
        // Arrange
        $userData = [
            'name' => 'Single User',
            'last_name' => 'Test',
            'email' => 'single@example.com',
            'phone_number' => '5511111111',
            'birthdate' => '1990-01-01',
            'gender' => \App\Core\Domain\Enum\User\UserGender::MUJER,
            'curp' => 'SING900101MDFRRN09',
            'password' => bcrypt('password'),
            'status' => UserStatus::ACTIVO,
            'registration_date' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Act
        $result = $this->repository->insertSingleUser($userData);

        // Assert
        $this->assertInstanceOf(EloquentUser::class, $result);
        $this->assertEquals('single@example.com', $result->email);

        $this->assertDatabaseHas('users', [
            'email' => 'single@example.com',
            'name' => 'Single User'
        ]);
    }

    #[Test]
    public function insert_single_user_throws_exception_on_error(): void
    {
        // Arrange - Intentar insertar sin email requerido
        $invalidData = [
            'name' => 'No Email User',
            // Falta email
        ];

        // Assert
        $this->expectException(\Exception::class);

        // Act
        $this->repository->insertSingleUser($invalidData);
    }

    // ==================== DELETION ELIMINATE USERS TESTS ====================

    #[Test]
    public function deletion_eliminate_users_removes_old_eliminated_users(): void
    {
        // Arrange
        $recentEliminated = EloquentUser::factory()->create([
            'status' => UserStatus::ELIMINADO,
            'mark_as_deleted_at' => now()->subDays(15) // Hace 15 días
        ]);

        $oldEliminated = EloquentUser::factory()->count(3)->create([
            'status' => UserStatus::ELIMINADO,
            'mark_as_deleted_at' => now()->subDays(45) // Hace 45 días (> 30)
        ]);

        $activeUser = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'mark_as_deleted_at' => now()->subDays(60) // Hace 60 días pero activo
        ]);

        // Crear notificaciones para usuarios eliminados
        foreach ($oldEliminated as $user) {
            DB::table('notifications')->insert([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'type' => 'TestNotification',
                'notifiable_type' => 'App\Models\User',
                'notifiable_id' => $user->id,
                'data' => json_encode(['test' => 'data']),
                'read_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Act
        $deletedCount = $this->repository->deletionEliminateUsers();

        // Assert
        $this->assertEquals(3, $deletedCount); // Solo los 3 antiguos eliminados

        // Verificar que los antiguos fueron eliminados
        foreach ($oldEliminated as $user) {
            $this->assertDatabaseMissing('users', ['id' => $user->id]);
            $this->assertDatabaseMissing('notifications', ['notifiable_id' => $user->id]);
        }

        // Verificar que el reciente eliminado sigue existiendo
        $this->assertDatabaseHas('users', ['id' => $recentEliminated->id]);

        // Verificar que el usuario activo sigue existiendo
        $this->assertDatabaseHas('users', ['id' => $activeUser->id]);
    }

    #[Test]
    public function deletion_eliminate_users_returns_zero_when_no_old_eliminated(): void
    {
        // Arrange
        EloquentUser::factory()->count(3)->create([
            'status' => UserStatus::ELIMINADO,
            'mark_as_deleted_at' => now()->subDays(15) // Todos recientes
        ]);

        // Act
        $deletedCount = $this->repository->deletionEliminateUsers();

        // Assert
        $this->assertEquals(0, $deletedCount);
        $this->assertEquals(3, EloquentUser::where('status', UserStatus::ELIMINADO)->count());
    }

    // ==================== CHANGE STATUS TESTS ====================

    #[Test]
    public function change_status_successfully(): void
    {
        // Arrange
        $user1 = EloquentUser::factory()->create(['status' => UserStatus::ACTIVO]);
        $user2 = EloquentUser::factory()->create(['status' => UserStatus::ACTIVO]);
        $user3 = EloquentUser::factory()->create(['status' => UserStatus::BAJA_TEMPORAL]);

        $userIds = [$user1->id, $user2->id, $user3->id];
        $newStatus = UserStatus::BAJA;

        // Act
        $result = $this->repository->changeStatus($userIds, $newStatus->value);

        // Assert
        $this->assertInstanceOf(UserChangedStatusResponse::class, $result);
        $this->assertEquals($newStatus->value, $result->newStatus);
        $this->assertEquals(3, $result->totalUpdated); // Todos cambiaron

        // Verificar que los estados cambiaron
        foreach ($userIds as $userId) {
            $user = EloquentUser::find($userId);
            $this->assertEquals($newStatus, $user->status);
        }
    }

    #[Test]
    public function change_status_ignores_users_already_in_target_status(): void
    {
        // Arrange
        $activeUser = EloquentUser::factory()->create(['status' => UserStatus::ACTIVO]);
        $alreadySuspended = EloquentUser::factory()->create(['status' => UserStatus::BAJA]);

        $userIds = [$activeUser->id, $alreadySuspended->id];
        $newStatus = UserStatus::BAJA; // alreadySuspended ya está en este estado

        // Act
        $result = $this->repository->changeStatus($userIds, $newStatus->value);

        // Assert
        $this->assertEquals(1, $result->totalUpdated); // Solo 1 cambió

        $activeUser->refresh();
        $alreadySuspended->refresh();

        $this->assertEquals($newStatus, $activeUser->status);
        $this->assertEquals($newStatus, $alreadySuspended->status); // Ya estaba así
    }

    #[Test]
    public function change_status_returns_zero_for_empty_array(): void
    {
        // Arrange
        $emptyArray = [];

        // Act
        $result = $this->repository->changeStatus($emptyArray, UserStatus::BAJA->value);

        // Assert
        $this->assertEquals(UserStatus::BAJA->value, $result->newStatus);
        $this->assertEquals(0, $result->totalUpdated);
    }

    #[Test]
    public function change_status_updates_timestamp(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'updated_at' => now()->subDays(1)
        ]);

        $originalUpdatedAt = $user->updated_at;

        // Act
        $this->repository->changeStatus([$user->id], UserStatus::BAJA->value);

        // Assert
        $user->refresh();
        $this->assertNotEquals($originalUpdatedAt, $user->updated_at);
        $this->assertTrue($user->updated_at->greaterThan($originalUpdatedAt));
    }

    // ==================== COMPREHENSIVE TESTS ====================

    #[Test]
    public function complete_user_lifecycle(): void
    {
        // 1. Crear usuario
        $userDTO = new CreateUserDTO(
            name: 'Carlos',
            last_name: 'García',
            email: 'carlos@example.com',
            password: 'password123',
            phone_number: '5522334455',
            curp: 'GARC920315HDFRRN09',
            birthdate: new Carbon('1992-03-15'),
            gender: \App\Core\Domain\Enum\User\UserGender::HOMBRE,
            address: [],
            status: UserStatus::ACTIVO
        );

        $createdUser = $this->repository->create($userDTO);
        $this->assertInstanceOf(\App\Models\User::class, $createdUser);
        $this->assertEquals('Carlos', $createdUser->name);

        // 2. Actualizar usuario
        $updatedUser = $this->repository->update($createdUser->id, [
            'name' => 'Carlos Eduardo'
        ]);
        $this->assertEquals('Carlos Eduardo', $updatedUser->name);

        // 3. Asignar rol
        $roleAssigned = $this->repository->assignRole($createdUser->id, UserRoles::STUDENT->value);
        $this->assertTrue($roleAssigned);

        // 4. Crear token
        $token = $this->repository->createToken($createdUser->id, 'Test Token');
        $this->assertNotEmpty($token);

        // 5. Cambiar estado
        $statusChange = $this->repository->changeStatus([$createdUser->id], UserStatus::BAJA_TEMPORAL->value);
        $this->assertEquals(1, $statusChange->totalUpdated);

        // 6. Verificar en base de datos
        $userInDb = EloquentUser::find($createdUser->id);
        $this->assertEquals(UserStatus::BAJA_TEMPORAL, $userInDb->status);
        $this->assertTrue($userInDb->hasRole(UserRoles::STUDENT->value));
    }

    #[Test]
    public function bulk_operations(): void
    {
        // 1. Insertar múltiples usuarios
        $usersData = [];
        for ($i = 1; $i <= 10; $i++) {
            $usersData[] = [
                'name' => "Bulk User $i",
                'last_name' => "Last $i",
                'email' => "bulk$i@example.com",
                'phone_number' => '551111111' . $i,
                'birthdate' => new Carbon('1990-01-' . ($i % 10 + 1)),
                'gender' => $i % 2 ? \App\Core\Domain\Enum\User\UserGender::HOMBRE : \App\Core\Domain\Enum\User\UserGender::MUJER,
                'curp' => 'BULK' . str_pad($i, 2, '0', STR_PAD_LEFT) . '0101HDFRRN09',
                'password' => bcrypt('password'),
                'status' => UserStatus::ACTIVO,
                'registration_date' => now(),
                'created_at' => now()->subDays($i),
                'updated_at' => now()->subDays($i),
            ];
        }

        $insertedUsers = $this->repository->insertManyUsers($usersData);
        $this->assertCount(10, $insertedUsers);

        // 2. Cambiar estado de algunos usuarios
        $userIds = $insertedUsers->take(3)->pluck('id')->toArray();
        $statusResult = $this->repository->changeStatus($userIds, UserStatus::BAJA->value);
        $this->assertEquals(3, $statusResult->totalUpdated);

        $eliminatedIds = $insertedUsers->slice(5, 5)->pluck('id')->toArray();
        $this->repository->changeStatus($eliminatedIds, UserStatus::ELIMINADO->value);

        // 3. Simular eliminación de usuarios antiguos eliminados
        // Forzar que algunos usuarios eliminados sean antiguos
        DB::table('users')
            ->whereIn('id', $insertedUsers->slice(5, 3)->pluck('id'))
            ->update(['mark_as_deleted_at' => now()->subDays(45)]);

        $deletedCount = $this->repository->deletionEliminateUsers();
        $this->assertEquals(3, $deletedCount); // Los 3 eliminados antiguos

        // 4. Verificar usuarios restantes
        $remainingUsers = EloquentUser::count();
        $this->assertEquals(7, $remainingUsers); // 10 - 3 eliminados
    }

    #[Test]
    public function token_management(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();

        // 1. Crear access token
        $accessToken = $this->repository->createToken($user->id, 'API Access');
        $this->assertNotEmpty($accessToken);

        // 2. Crear refresh token
        $user->assignRole(UserRoles::STUDENT->value);
        $refreshToken = $this->repository->createRefreshToken($user->id, 'Refresh');
        $this->assertNotEmpty($refreshToken);

        // 3. Verificar que ambos tokens existen
        $accessTokenRecord = PersonalAccessToken::findToken($accessToken);
        $this->assertNotNull($accessTokenRecord);

        $hashedRefreshToken = hash('sha256', $refreshToken);
        $refreshTokenRecord = DB::table('refresh_tokens')
            ->where('user_id', $user->id)
            ->where('token', $hashedRefreshToken)
            ->first();
        $this->assertNotNull($refreshTokenRecord);

        // 4. Crear múltiples tokens
        $token2 = $this->repository->createToken($user->id, 'Mobile App');
        $token3 = $this->repository->createToken($user->id, 'Web Client');

        $tokenCount = PersonalAccessToken::where('tokenable_id', $user->id)->count();
        $this->assertEquals(3, $tokenCount);
    }

    #[Test]
    public function user_with_multiple_roles(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();

        // Act - Asignar múltiples roles secuencialmente
        $this->repository->assignRole($user->id, UserRoles::STUDENT->value);
        $this->repository->assignRole($user->id, UserRoles::UNVERIFIED->value);

        // También asignar role de admin
        $user->assignRole(UserRoles::ADMIN->value);

        // Assert
        $user->refresh();
        $this->assertTrue($user->hasRole(UserRoles::STUDENT->value));
        $this->assertTrue($user->hasRole(UserRoles::ADMIN->value));
        $this->assertTrue($user->hasRole(UserRoles::UNVERIFIED->value));
    }

    #[Test]
    public function edge_cases_and_error_handling(): void
    {
        // 1. Usuario no encontrado al actualizar
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->update(999999, ['name' => 'Test']);

        // 2. Usuario no encontrado al crear token
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->createToken(999999, 'Test');

        // 3. Insertar usuario con datos inválidos
        $this->expectException(\Exception::class);
        $this->repository->insertSingleUser(['invalid' => 'data']);

        // 4. Cambiar estado con IDs inválidos (no debería lanzar excepción)
        $result = $this->repository->changeStatus([999999, 888888], UserStatus::BAJA->value);
        $this->assertEquals(0, $result->totalUpdated);

        // 5. Deletion con base de datos vacía
        $deletedCount = $this->repository->deletionEliminateUsers();
        $this->assertEquals(0, $deletedCount);
    }

}
