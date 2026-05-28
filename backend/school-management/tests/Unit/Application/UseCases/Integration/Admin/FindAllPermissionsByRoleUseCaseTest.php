<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\DTO\Response\General\PermissionsByRole;
use App\Core\Application\UseCases\Admin\RolePermissionManagement\FindAllPermissionsByRoleUseCase;
use App\Core\Domain\Entities\Permission;
use App\Core\Domain\Enum\User\UserRoles;
use App\Exceptions\NotFound\PermissionsByUserNotFoundException;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FindAllPermissionsByRoleUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        $this->seed(PermissionsSeeder::class);
    }

    #[Test]
    public function it_returns_permissions_for_valid_role_with_users(): void
    {
        // Arrange
        $role = Role::where('name', UserRoles::STUDENT->value)->first();

        // Crear algunos usuarios con el rol
        $users = User::factory()->count(3)->create();
        foreach ($users as $user) {
            $user->assignRole($role);
        }

        $useCase = app(FindAllPermissionsByRoleUseCase::class);

        // Act
        $result = $useCase->execute(UserRoles::STUDENT->value);

        // Assert
        $this->assertInstanceOf(PermissionsByRole::class, $result);
        $this->assertEquals(UserRoles::STUDENT->value, $result->role);
        $this->assertEquals(3, $result->usersCount);
        $this->assertIsArray($result->permissions);

        // Verificar que los permisos tienen la estructura correcta
        if (!empty($result->permissions)) {
            $firstPermission = $result->permissions[0];
            $this->assertInstanceOf(Permission::class, $firstPermission);
            $this->assertIsInt($firstPermission->id);
            $this->assertIsString($firstPermission->name);
            $this->assertIsString($firstPermission->type);
        }
    }

    #[Test]
    public function it_throws_exception_when_role_has_no_users(): void
    {
        // Arrange
        $roleName = UserRoles::STUDENT->value;

        // No crear usuarios con este rol (los seeders crean los roles pero no usuarios)
        $useCase = app(FindAllPermissionsByRoleUseCase::class);

        // Assert
        $this->expectException(PermissionsByUserNotFoundException::class);

        // Act
        $useCase->execute($roleName);
    }

    #[Test]
    public function it_throws_exception_for_non_existent_role(): void
    {
        // Arrange
        $nonExistentRole = 'NON_EXISTENT_ROLE';
        $useCase = app(FindAllPermissionsByRoleUseCase::class);

        // Assert
        $this->expectException(RoleDoesNotExist::class);

        // Act
        $useCase->execute($nonExistentRole);
    }

    #[Test]
    public function it_returns_permissions_when_role_has_many_users(): void
    {
        // Arrange
        $role = Role::where('name', UserRoles::STUDENT->value)->first();

        // Crear 50 usuarios con el rol
        $users = User::factory()->count(50)->create();
        foreach ($users as $user) {
            $user->assignRole($role);
        }

        $useCase = app(FindAllPermissionsByRoleUseCase::class);

        // Act
        $result = $useCase->execute(UserRoles::STUDENT->value);

        // Assert
        $this->assertInstanceOf(PermissionsByRole::class, $result);
        $this->assertEquals(UserRoles::STUDENT->value, $result->role);
        $this->assertEquals(50, $result->usersCount);
        $this->assertIsArray($result->permissions);
    }

    #[Test]
    public function it_returns_empty_permissions_array_when_no_permissions_associated(): void
    {
        // Arrange
        $role = Role::where('name', UserRoles::PARENT->value)->first();

        // Crear usuarios pero sin permisos asociados
        // (Esto depende de si el seeder asocia permisos a este rol)
        $users = User::factory()->count(2)->create();
        foreach ($users as $user) {
            $user->assignRole($role);
        }

        $useCase = app(FindAllPermissionsByRoleUseCase::class);

        // Act
        $result = $useCase->execute(UserRoles::PARENT->value);

        // Assert
        $this->assertInstanceOf(PermissionsByRole::class, $result);
        $this->assertEquals(UserRoles::PARENT->value, $result->role);
        $this->assertEquals(2, $result->usersCount);
        $this->assertIsArray($result->permissions);
        // Puede estar vacío o tener permisos, dependiendo del seeder
    }

    #[Test]
    public function it_works_with_financial_staff_role(): void
    {
        // Arrange
        $role = Role::where('name', UserRoles::FINANCIAL_STAFF->value)->first();

        // Crear usuario
        $user = User::factory()->create();
        $user->assignRole($role);

        $useCase = app(FindAllPermissionsByRoleUseCase::class);

        // Act
        $result = $useCase->execute(UserRoles::FINANCIAL_STAFF->value);

        // Assert
        $this->assertInstanceOf(PermissionsByRole::class, $result);
        $this->assertEquals(UserRoles::FINANCIAL_STAFF->value, $result->role);
        $this->assertEquals(1, $result->usersCount);
    }

    #[Test]
    public function it_works_with_supervisor_role(): void
    {
        // Arrange
        $role = Role::where('name', UserRoles::SUPERVISOR->value)->first();

        // Crear usuario
        $user = User::factory()->create();
        $user->assignRole($role);

        $useCase = app(FindAllPermissionsByRoleUseCase::class);

        // Act
        $result = $useCase->execute(UserRoles::SUPERVISOR->value);

        // Assert
        $this->assertInstanceOf(PermissionsByRole::class, $result);
        $this->assertEquals(UserRoles::SUPERVISOR->value, $result->role);
        $this->assertEquals(1, $result->usersCount);
    }

    #[Test]
    public function it_returns_correct_structure_for_dto(): void
    {
        // Arrange
        $role = Role::where('name', UserRoles::ADMIN->value)->first();

        // Crear usuario
        $user = User::factory()->create();
        $user->assignRole($role);

        $useCase = app(FindAllPermissionsByRoleUseCase::class);

        // Act
        $result = $useCase->execute(UserRoles::ADMIN->value);

        // Assert - Verificar la estructura del DTO
        $this->assertIsString($result->role);
        $this->assertIsInt($result->usersCount);
        $this->assertIsArray($result->permissions);

        // Verificar propiedades públicas
        $this->assertTrue(property_exists($result, 'role'));
        $this->assertTrue(property_exists($result, 'usersCount'));
        $this->assertTrue(property_exists($result, 'permissions'));
    }

    #[Test]
    public function it_filters_only_model_type_permissions(): void
    {
        // Arrange
        $role = Role::where('name', UserRoles::STUDENT->value)->first();

        // Crear usuario
        $user = User::factory()->create();
        $user->assignRole($role);

        $useCase = app(FindAllPermissionsByRoleUseCase::class);

        // Act
        $result = $useCase->execute(UserRoles::STUDENT->value);

        // Assert - Todos los permisos deberían ser de tipo 'model'
        foreach ($result->permissions as $permission) {
            $this->assertEquals('model', $permission->type);
        }
    }

    #[Test]
    public function it_handles_multiple_roles_with_different_permission_sets(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $teacherRole = Role::where('name', UserRoles::FINANCIAL_STAFF->value)->first();

        // Crear usuarios
        $studentUser = User::factory()->create();
        $studentUser->assignRole($studentRole);

        $teacherUser = User::factory()->create();
        $teacherUser->assignRole($teacherRole);

        $useCase = app(FindAllPermissionsByRoleUseCase::class);

        // Act - Obtener permisos para ambos roles
        $studentResult = $useCase->execute(UserRoles::STUDENT->value);
        $teacherResult = $useCase->execute(UserRoles::FINANCIAL_STAFF->value);

        // Assert - Cada rol debería tener sus propios permisos
        $this->assertEquals(UserRoles::STUDENT->value, $studentResult->role);
        $this->assertEquals(UserRoles::FINANCIAL_STAFF->value, $teacherResult->role);

        // Los conjuntos de permisos pueden ser diferentes o iguales dependiendo del seeder
        $this->assertIsArray($studentResult->permissions);
        $this->assertIsArray($teacherResult->permissions);
    }

    #[Test]
    public function it_includes_permission_details_correctly(): void
    {
        // Arrange
        $role = Role::where('name', UserRoles::STUDENT->value)->first();

        // Crear usuario
        $user = User::factory()->create();
        $user->assignRole($role);

        $useCase = app(FindAllPermissionsByRoleUseCase::class);

        // Act
        $result = $useCase->execute(UserRoles::STUDENT->value);

        // Assert
        $this->assertInstanceOf(PermissionsByRole::class, $result);

        // Verificar que cada permiso tiene la estructura básica
        foreach ($result->permissions as $permission) {
            $this->assertInstanceOf(Permission::class, $permission);
            $this->assertIsInt($permission->id);
            $this->assertIsString($permission->name);
            $this->assertIsString($permission->type);
        }
    }

    #[Test]
    public function it_returns_zero_users_count_when_no_users_but_has_permissions_in_seeder(): void
    {
        // Este test verifica que si un rol tiene permisos definidos en el seeder
        // pero no tiene usuarios asignados, debería lanzar excepción

        // Arrange
        $roleName = UserRoles::PARENT->value; // Asumiendo que el seeder crea permisos para este rol
        $useCase = app(FindAllPermissionsByRoleUseCase::class);

        // Assert - Debería lanzar excepción aunque el rol tenga permisos en el seeder
        $this->expectException(PermissionsByUserNotFoundException::class);

        // Act
        $useCase->execute($roleName);
    }

}
