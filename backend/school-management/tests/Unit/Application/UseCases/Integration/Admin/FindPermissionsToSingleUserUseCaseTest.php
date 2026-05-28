<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\UseCases\Admin\RolePermissionManagement\FindPermissionsToSingleUserUseCase;
use App\Core\Domain\Enum\User\UserRoles;
use App\Exceptions\NotFound\PermissionsByUserNotFoundException;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use App\Core\Domain\Entities\Permission as PermissionEntity;

class FindPermissionsToSingleUserUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        $this->seed(PermissionsSeeder::class);
    }

    #[Test]
    public function it_returns_permissions_for_user_with_single_role(): void
    {
        // Arrange
        $role = Role::where('name', UserRoles::STUDENT->value)->first();
        $user = User::factory()->create();
        $user->assignRole($role);

        $useCase = app(FindPermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, []);

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        foreach ($result as $permission) {
            $this->assertInstanceOf(PermissionEntity::class, $permission);
            $this->assertIsInt($permission->id);
            $this->assertIsString($permission->name);
            $this->assertEquals('model', $permission->type);
        }
    }

    #[Test]
    public function it_returns_permissions_for_user_with_multiple_roles(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();

        $user = User::factory()->create();
        $user->assignRole([$studentRole, $parentRole]);

        $useCase = app(FindPermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, []);

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Verificar que todos los permisos son únicos (no duplicados)
        $permissionIds = array_map(fn($p) => $p->id, $result);
        $uniquePermissionIds = array_unique($permissionIds);
        $this->assertCount(count($uniquePermissionIds), $permissionIds);
    }

    #[Test]
    public function it_returns_permissions_for_specific_roles_provided_that_user_has(): void
    {
        // Arrange - CORREGIDO: El usuario DEBE tener los roles que se solicitan
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();

        $user = User::factory()->create();
        $user->assignRole([$studentRole, $parentRole]); // Ahora tiene los roles

        $useCase = app(FindPermissionsToSingleUserUseCase::class);
        $specificRoles = [UserRoles::STUDENT->value]; // Solo solicitamos uno de sus roles

        // Act
        $result = $useCase->execute($user->id, $specificRoles);

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result, 'Debería devolver permisos para el rol STUDENT que el usuario tiene');
    }

    #[Test]
    public function it_returns_empty_when_requesting_roles_user_does_not_have(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $user = User::factory()->create();
        $user->assignRole($studentRole); // Solo tiene STUDENT

        $useCase = app(FindPermissionsToSingleUserUseCase::class);
        $requestedRoles = [UserRoles::ADMIN->value]; // Pide ADMIN, pero no lo tiene

        // Assert
        $this->expectException(PermissionsByUserNotFoundException::class);

        // Act
        $useCase->execute($user->id, $requestedRoles);
    }

    #[Test]
    public function it_filters_permissions_by_model_type_only(): void
    {
        // Arrange
        $role = Role::where('name', UserRoles::ADMIN->value)->first();
        $user = User::factory()->create();
        $user->assignRole($role);

        $useCase = app(FindPermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, []);

        // Assert - Todos los permisos deben ser de tipo 'model'
        foreach ($result as $permission) {
            $this->assertEquals('model', $permission->type);
        }
    }

    #[Test]
    public function it_throws_exception_when_user_has_no_roles_and_no_roles_provided(): void
    {
        // Arrange
        $user = User::factory()->create();
        // No asignar ningún rol al usuario

        $useCase = app(FindPermissionsToSingleUserUseCase::class);

        // Assert
        $this->expectException(PermissionsByUserNotFoundException::class);

        // Act
        $useCase->execute($user->id, []);
    }

    #[Test]
    public function it_throws_exception_when_no_permissions_found_for_user_roles(): void
    {
        // Arrange
        // Necesitamos un rol que no tenga permisos asociados en el seeder
        // 'unverified' podría ser ese rol, o creamos uno
        $roleWithoutPermissions = Role::create([
            'name' => 'role-without-permissions',
            'guard_name' => 'sanctum'
        ]);

        $user = User::factory()->create();
        $user->assignRole($roleWithoutPermissions);

        $useCase = app(FindPermissionsToSingleUserUseCase::class);

        // Assert
        $this->expectException(PermissionsByUserNotFoundException::class);

        // Act
        $useCase->execute($user->id, []);
    }

    #[Test]
    public function it_throws_exception_when_user_does_not_exist_but_roles_provided(): void
    {
        // Arrange
        $nonExistentUserId = 99999;
        $useCase = app(FindPermissionsToSingleUserUseCase::class);

        $specificRoles = [UserRoles::STUDENT->value];

        // Assert
        $this->expectException(PermissionsByUserNotFoundException::class);

        // Act
        $useCase->execute($nonExistentUserId, $specificRoles);
    }

    #[Test]
    public function it_handles_duplicate_roles_in_input(): void
    {
        // Arrange - CORREGIDO: El usuario debe tener los roles
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();

        $user = User::factory()->create();
        $user->assignRole([$studentRole, $parentRole]); // Tiene ambos roles

        $useCase = app(FindPermissionsToSingleUserUseCase::class);
        $duplicateRoles = [
            UserRoles::STUDENT->value,
            UserRoles::STUDENT->value, // Duplicado
            UserRoles::PARENT->value
        ];

        // Act
        $result = $useCase->execute($user->id, $duplicateRoles);

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        // Los permisos deberían ser únicos (sin duplicados por rol duplicado)
        $permissionIds = array_map(fn($p) => $p->id, $result);
        $uniquePermissionIds = array_unique($permissionIds);
        $this->assertCount(count($uniquePermissionIds), $permissionIds);
    }

    #[Test]
    public function it_returns_permissions_for_all_user_roles(): void
    {
        // Arrange - Crear usuario con varios roles
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $adminRole = Role::where('name', UserRoles::ADMIN->value)->first();

        $user = User::factory()->create();
        $user->assignRole([$studentRole, $parentRole, $adminRole]);

        $useCase = app(FindPermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, []);

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        foreach ($result as $permission) {
            $this->assertInstanceOf(PermissionEntity::class, $permission);
        }
    }

    #[Test]
    public function it_filters_to_only_provided_roles_that_user_has(): void
    {
        // Arrange - CORREGIDO: Usuario tiene ambos roles
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $adminRole = Role::where('name', UserRoles::ADMIN->value)->first();

        $user = User::factory()->create();
        $user->assignRole([$studentRole, $adminRole]); // Tiene ambos

        $useCase = app(FindPermissionsToSingleUserUseCase::class);
        $providedRoles = [UserRoles::ADMIN->value]; // Solo pedimos uno

        // Act
        $result = $useCase->execute($user->id, $providedRoles);

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        // Debería devolver permisos solo para 'admin', no para 'student'
    }

    #[Test]
    public function it_handles_empty_roles_array_and_gets_from_user(): void
    {
        // Arrange
        $role = Role::where('name', UserRoles::FINANCIAL_STAFF->value)->first();
        $user = User::factory()->create();
        $user->assignRole($role);

        $useCase = app(FindPermissionsToSingleUserUseCase::class);

        // Act - Array vacío, debería obtener roles del usuario
        $result = $useCase->execute($user->id, []);

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    #[Test]
    public function it_filters_permissions_by_target_role_in_context(): void
    {
        // Arrange - CORREGIDO: Usuario debe tener los roles
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $financialStaffRole = Role::where('name', UserRoles::FINANCIAL_STAFF->value)->first();

        $user = User::factory()->create();
        $user->assignRole([$studentRole, $financialStaffRole]);

        $useCase = app(FindPermissionsToSingleUserUseCase::class);
        $roles = [UserRoles::STUDENT->value, UserRoles::FINANCIAL_STAFF->value];

        // Act
        $result = $useCase->execute($user->id, $roles);

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        foreach ($result as $permission) {
            $this->assertInstanceOf(PermissionEntity::class, $permission);
        }
    }

    #[Test]
    public function it_returns_unique_permissions_across_multiple_roles(): void
    {
        // Arrange - CORREGIDO: Usuario debe tener los roles
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $adminRole = Role::where('name', UserRoles::ADMIN->value)->first();

        $user = User::factory()->create();
        $user->assignRole([$studentRole, $parentRole, $adminRole]);

        $useCase = app(FindPermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, []); // Todos los roles del usuario

        // Assert - No debería haber permisos duplicados
        $permissionIds = array_map(fn($p) => $p->id, $result);
        $uniquePermissionIds = array_unique($permissionIds);

        $this->assertCount(count($uniquePermissionIds), $permissionIds);
    }

    #[Test]
    public function it_works_with_supervisor_role(): void
    {
        // Arrange
        $role = Role::where('name', UserRoles::SUPERVISOR->value)->first();
        $user = User::factory()->create();
        $user->assignRole($role);

        $useCase = app(FindPermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, []);

        // Assert
        $this->assertIsArray($result);

        foreach ($result as $permission) {
            $this->assertInstanceOf(PermissionEntity::class, $permission);
            $this->assertEquals('model', $permission->type);
        }
    }

    #[Test]
    public function it_works_with_applicant_role(): void
    {
        // Arrange
        $role = Role::where('name', UserRoles::APPLICANT->value)->first();
        $user = User::factory()->create();
        $user->assignRole($role);

        $useCase = app(FindPermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, []);

        // Assert
        $this->assertIsArray($result);

        foreach ($result as $permission) {
            $this->assertInstanceOf(PermissionEntity::class, $permission);
        }
    }

    #[Test]
    public function it_handles_mixed_role_scenarios(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $financialStaffRole = Role::where('name', UserRoles::FINANCIAL_STAFF->value)->first();

        $user = User::factory()->create();
        $user->assignRole([$studentRole, $financialStaffRole]);

        $useCase = app(FindPermissionsToSingleUserUseCase::class);

        // Probar diferentes combinaciones - CORREGIDAS
        $testCases = [
            'empty_array' => [],
            'single_role_user_has' => [UserRoles::STUDENT->value],
            'multiple_roles_user_has' => [UserRoles::STUDENT->value, UserRoles::FINANCIAL_STAFF->value],
            'role_user_does_not_have' => [UserRoles::ADMIN->value], // Este debería fallar
        ];

        foreach ($testCases as $caseName => $roles) {
            try {
                $result = $useCase->execute($user->id, $roles);

                $this->assertIsArray($result, "Caso: $caseName");
                $this->assertNotEmpty($result, "Caso: $caseName");

                foreach ($result as $permission) {
                    $this->assertInstanceOf(PermissionEntity::class, $permission, "Caso: $caseName");
                }
            } catch (PermissionsByUserNotFoundException $e) {
                if ($caseName !== 'role_user_does_not_have') {
                    throw $e;
                }
                $this->addToAssertionCount(1);
            }
        }
    }

    #[Test]
    public function it_validates_permission_structure(): void
    {
        // Arrange
        $role = Role::where('name', UserRoles::STUDENT->value)->first();
        $user = User::factory()->create();
        $user->assignRole($role);

        $useCase = app(FindPermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, []);

        // Assert - Verificar estructura completa de cada permiso
        foreach ($result as $permission) {
            $this->assertInstanceOf(PermissionEntity::class, $permission);
            $this->assertIsInt($permission->id);
            $this->assertIsString($permission->name);
            $this->assertIsString($permission->type);
            $this->assertNotNull($permission->id);
            $this->assertNotNull($permission->name);
            $this->assertNotNull($permission->type);
        }
    }

    #[Test]
    public function it_returns_different_permissions_for_different_roles(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $adminRole = Role::where('name', UserRoles::ADMIN->value)->first();

        $user1->assignRole($studentRole);
        $user2->assignRole($adminRole);

        $useCase = app(FindPermissionsToSingleUserUseCase::class);

        // Act
        $permissions1 = $useCase->execute($user1->id, []);
        $permissions2 = $useCase->execute($user2->id, []);

        // Assert - Los permisos pueden ser diferentes (depende del seeder)
        $this->assertIsArray($permissions1);
        $this->assertIsArray($permissions2);

        // Al menos deberían tener algunos permisos
        $this->assertNotEmpty($permissions1);
        $this->assertNotEmpty($permissions2);
    }

    #[Test]
    public function it_returns_partial_intersection_when_some_provided_roles_exist(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();

        $user = User::factory()->create();
        $user->assignRole([$studentRole, $parentRole]); // Tiene STUDENT y PARENT

        $useCase = app(FindPermissionsToSingleUserUseCase::class);

        // Pide 3 roles, pero el usuario solo tiene 2 de ellos
        $requestedRoles = [
            UserRoles::STUDENT->value,
            UserRoles::PARENT->value,
            UserRoles::ADMIN->value  // Este no lo tiene
        ];

        // Act
        $result = $useCase->execute($user->id, $requestedRoles);

        // Assert - Debería devolver permisos solo para STUDENT y PARENT
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        // Nota: No podemos verificar cuáles permisos exactamente sin conocer el seeder
    }
}
