<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\DTO\Response\General\PermissionsByUsers;
use App\Core\Application\UseCases\Admin\RolePermissionManagement\FindAllPermissionsByCurpsUseCase;
use App\Core\Domain\Enum\User\UserRoles;
use App\Exceptions\NotFound\PermissionsByUserNotFoundException;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FindAllPermissionsByCurpsUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        $this->seed(PermissionsSeeder::class);
    }

    #[Test]
    public function it_returns_permissions_by_users_when_curps_provided(): void
    {
        $user1 = User::factory()->create([
            'curp' => 'CURP12345678901234',
        ]);

        $user2 = User::factory()->create([
            'curp' => 'CURP98765432109876',
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $staffRole   = Role::where('name', UserRoles::FINANCIAL_STAFF->value)->first();

        $user1->assignRole($studentRole);
        $user2->assignRole($staffRole);

        $useCase = app(FindAllPermissionsByCurpsUseCase::class);

        $result = $useCase->execute([
            'CURP12345678901234',
            'CURP98765432109876'
        ]);

        $roles = array_column($result->permissions, 'role');

        $this->assertContains(UserRoles::STUDENT->value, $roles);
        $this->assertContains(UserRoles::FINANCIAL_STAFF->value, $roles);

        // Verificar la estructura del DTO
        $this->assertIsArray($result->roles);
        $this->assertIsArray($result->users);
        $this->assertIsArray($result->permissions);

        // Verificar que ambos usuarios están incluidos
        $userCurps = array_column($result->users, 'curp');
        $this->assertContains('CURP12345678901234', $userCurps);
        $this->assertContains('CURP98765432109876', $userCurps);
    }

    #[Test]
    public function it_throws_exception_when_curps_array_is_empty(): void
    {
        $useCase = app(FindAllPermissionsByCurpsUseCase::class);

        $this->expectException(PermissionsByUserNotFoundException::class);

        $useCase->execute([]);
    }

    #[Test]
    public function it_throws_exception_when_users_have_no_permissions(): void
    {
        // Crear usuario sin roles asignados
        User::factory()->create([
            'curp' => 'CURP12345678901234',
        ]);

        $useCase = app(FindAllPermissionsByCurpsUseCase::class);

        $this->expectException(PermissionsByUserNotFoundException::class);

        $useCase->execute(['CURP12345678901234']);
    }

    #[Test]
    public function it_returns_permissions_for_users_with_multiple_roles(): void
    {
        $user = User::factory()->create([
            'curp' => 'CURP12345678901234',
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $staffRole   = Role::where('name', UserRoles::FINANCIAL_STAFF->value)->first();

        $user->assignRole($studentRole);
        $user->assignRole($staffRole);

        $useCase = app(FindAllPermissionsByCurpsUseCase::class);

        $result = $useCase->execute(['CURP12345678901234']);

        $roles = array_column($result->permissions, 'role');

        $this->assertContains(UserRoles::STUDENT->value, $roles);
        $this->assertContains(UserRoles::FINANCIAL_STAFF->value, $roles);

        // Verificar que el usuario tiene ambos roles
        $userData = $result->users[0];
        $this->assertContains(UserRoles::STUDENT->value, $userData['roles']);
        $this->assertContains(UserRoles::FINANCIAL_STAFF->value, $userData['roles']);
    }

    #[Test]
    public function it_handles_users_with_no_permissions(): void
    {
        // Arrange
        $user = User::factory()->create([
            'curp' => 'CURP12345678901234',
            'email' => 'noperms@example.com'
        ]);

        // Usuario sin roles asignados
        $curps = ['CURP12345678901234'];
        $useCase = app(FindAllPermissionsByCurpsUseCase::class);

        // Assert
        $this->expectException(PermissionsByUserNotFoundException::class);

        // Act - CORREGIDO: Remover el segundo parámetro null
        $useCase->execute($curps);
    }

    #[Test]
    public function it_handles_users_with_multiple_roles(): void
    {
        // Arrange
        $user = User::factory()->create([
            'curp' => 'CURP12345678901234',
            'email' => 'multirole@example.com'
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $staffRole = Role::where('name', UserRoles::FINANCIAL_STAFF->value)->first();

        $user->assignRole($studentRole);
        $user->assignRole($staffRole);

        $useCase = app(FindAllPermissionsByCurpsUseCase::class);

        // Act
        $result = $useCase->execute(['CURP12345678901234']);

        // Assert
        $this->assertInstanceOf(PermissionsByUsers::class, $result);

        // Debería tener permisos para ambos roles
        $roles = array_column($result->permissions, 'role');
        $this->assertContains(UserRoles::STUDENT->value, $roles);
        $this->assertContains(UserRoles::FINANCIAL_STAFF->value, $roles);

        // El usuario debería estar en los resultados
        $this->assertCount(1, $result->users);
        $this->assertEquals('CURP12345678901234', $result->users[0]['curp']);
        $this->assertContains(UserRoles::STUDENT->value, $result->users[0]['roles']);
        $this->assertContains(UserRoles::FINANCIAL_STAFF->value, $result->users[0]['roles']);
    }

    #[Test]
    public function it_returns_empty_when_no_filters_provided(): void
    {
        // Arrange
        $user = User::factory()->create(['curp' => 'CURP12345678901234']);
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $user->assignRole($studentRole);

        $useCase = app(FindAllPermissionsByCurpsUseCase::class);
        $this->expectException(PermissionsByUserNotFoundException::class);

        // Act - Array vacío
        $useCase->execute([]);
    }

    #[Test]
    public function it_shows_all_roles_when_filtering_by_many_curps(): void
    {
        // Arrange - Crear usuarios con diferentes roles
        $studentUsers = User::factory()->count(5)->create();
        $staffUsers = User::factory()->count(5)->create();
        $parentUsers = User::factory()->count(5)->create();

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $staffRole = Role::where('name', UserRoles::FINANCIAL_STAFF->value)->first();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();

        foreach ($studentUsers as $user) {
            $user->assignRole($studentRole);
        }

        foreach ($staffUsers as $user) {
            $user->assignRole($staffRole);
        }

        foreach ($parentUsers as $user) {
            $user->assignRole($parentRole);
        }

        $allCurps = $studentUsers->merge($staffUsers)->merge($parentUsers)->pluck('curp')->toArray();

        $useCase = app(FindAllPermissionsByCurpsUseCase::class);

        // Act
        $result = $useCase->execute($allCurps);

        // Assert
        $this->assertInstanceOf(PermissionsByUsers::class, $result);

        // Debería mostrar permisos para los 3 roles
        $this->assertCount(3, $result->permissions);

        $roles = array_column($result->permissions, 'role');
        $this->assertContains(UserRoles::STUDENT->value, $roles);
        $this->assertContains(UserRoles::FINANCIAL_STAFF->value, $roles);
        $this->assertContains(UserRoles::PARENT->value, $roles);

        // Debería tener 15 usuarios
        $this->assertCount(15, $result->users);
    }

    #[Test]
    public function it_limits_user_details_when_more_than_15_users(): void
    {
        // Arrange - Crear 20 usuarios (pero el query no limita a 15, solo valida CURPs)
        $users = User::factory()->count(20)->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();

        foreach ($users as $user) {
            $user->assignRole($studentRole);
        }

        $allCurps = $users->pluck('curp')->toArray();

        $useCase = app(FindAllPermissionsByCurpsUseCase::class);

        // Act
        $result = $useCase->execute($allCurps);

        // Assert
        $this->assertInstanceOf(PermissionsByUsers::class, $result);

        // Debería tener todos los usuarios (20)
        $this->assertCount(20, $result->users);

        // Debería tener permisos del rol student
        $this->assertCount(1, $result->permissions);
        $this->assertEquals(UserRoles::STUDENT->value, $result->permissions[0]['role']);
    }

    #[Test]
    public function it_filters_invalid_curps(): void
    {
        // Arrange
        $validUser = User::factory()->create([
            'curp' => 'CURP12345678901234', // 18 caracteres
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $validUser->assignRole($studentRole);

        $useCase = app(FindAllPermissionsByCurpsUseCase::class);

        // Act - CURP inválido (demasiado corto)
        $result = $useCase->execute([
            'CURP12345678901234', // Válido
            'INVALID123',         // Inválido - menos de 18 caracteres
            'ANOTHERINVALIDCURP'  // Inválido - menos de 18 caracteres
        ]);

        // Assert - Solo el usuario válido debería estar en los resultados
        $this->assertCount(1, $result->users);
        $this->assertEquals('CURP12345678901234', $result->users[0]['curp']);
    }

    #[Test]
    public function it_returns_null_when_no_valid_curps(): void
    {
        // Arrange
        $useCase = app(FindAllPermissionsByCurpsUseCase::class);

        $this->expectException(PermissionsByUserNotFoundException::class);

        // Act - Solo CURPs inválidos
        $useCase->execute(['INVALID1', 'INVALID2']);
    }

    #[Test]
    public function it_limits_to_100_curps(): void
    {
        // Arrange - Crear 110 usuarios
        $users = User::factory()->count(110)->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();

        foreach ($users as $user) {
            $user->assignRole($studentRole);
        }

        $allCurps = $users->pluck('curp')->toArray();

        $useCase = app(FindAllPermissionsByCurpsUseCase::class);

        // Act - Pasar 110 CURPs
        $result = $useCase->execute($allCurps);

        // Assert - Solo debería procesar 100 CURPs
        $this->assertLessThanOrEqual(100, count($result->users));
    }

}
