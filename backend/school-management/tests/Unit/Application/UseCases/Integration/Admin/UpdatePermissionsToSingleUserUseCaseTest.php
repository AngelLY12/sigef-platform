<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\DTO\Response\General\PermissionsUpdatedToUserResponse;
use App\Core\Application\UseCases\Admin\RolePermissionManagement\UpdatePermissionsToSingleUserUseCase;
use App\Core\Domain\Enum\User\UserRoles;
use App\Exceptions\Validation\ValidationException;
use App\Models\Permission;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdatePermissionsToSingleUserUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        $this->seed(PermissionsSeeder::class);
    }

    #[Test]
    public function it_adds_permissions_to_user_successfully(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'John',
            'last_name' => 'Doe'
        ]);
        $role = Role::where('name', UserRoles::STUDENT->value)->first();
        $user->assignRole($role);

        // Obtener algunos permisos existentes
        $permissions = Permission::where('type', 'model')->take(3)->get();
        $permissionNames = $permissions->pluck('name')->toArray();

        $permissionsToAdd = $permissionNames;
        $permissionsToRemove = [];

        $useCase = app(UpdatePermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $permissionsToAdd, $permissionsToRemove);

        // Assert
        $this->assertInstanceOf(PermissionsUpdatedToUserResponse::class, $result);
        $this->assertEquals($user->id, $result->userId);
        $this->assertEquals('John Doe', $result->fullName);

        // Verificar estructura de permissions
        $this->assertIsArray($result->permissions);
        $this->assertArrayHasKey('permissionsAdded', $result->permissions);
        $this->assertArrayHasKey('permissionsRemoved', $result->permissions);
        $this->assertArrayHasKey('currentPermissions', $result->permissions);

        $this->assertEquals(count($permissionsToAdd), count($result->permissions['permissionsAdded']));
        $this->assertEmpty($result->permissions['permissionsRemoved']);

        // Verificar que los permisos fueron realmente asignados
        $user->refresh();
        foreach ($permissionsToAdd as $permissionName) {
            $this->assertTrue($user->hasPermissionTo($permissionName));
        }
    }

    #[Test]
    public function it_removes_permissions_from_user_successfully(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Jane',
            'last_name' => 'Smith'
        ]);
        $role = Role::where('name', UserRoles::STUDENT->value)->first();
        $user->assignRole($role);

        // Asignar algunos permisos primero
        $permissions = Permission::where('type', 'model')->take(3)->get();
        $permissionNames = $permissions->pluck('name')->toArray();
        $user->givePermissionTo($permissionNames);

        $permissionsToAdd = [];
        $permissionsToRemove = [$permissionNames[0], $permissionNames[1]]; // Remover 2 de 3

        $useCase = app(UpdatePermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $permissionsToAdd, $permissionsToRemove);

        // Assert
        $this->assertInstanceOf(PermissionsUpdatedToUserResponse::class, $result);
        $this->assertEquals($user->id, $result->userId);
        $this->assertEquals('Jane Smith', $result->fullName);

        $this->assertEmpty($result->permissions['permissionsAdded']);
        $this->assertEquals(count($permissionsToRemove), count($result->permissions['permissionsRemoved']));

        // Verificar que los permisos fueron realmente removidos
        $user->refresh();
        foreach ($permissionsToRemove as $permissionName) {
            $this->assertFalse($user->hasPermissionTo($permissionName));
        }
        // El permiso no removido debería seguir existiendo
        $this->assertTrue($user->hasPermissionTo($permissionNames[2]));
    }

    #[Test]
    public function it_adds_and_removes_permissions_simultaneously(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Alice',
            'last_name' => 'Johnson'
        ]);
        $role = Role::where('name', UserRoles::STUDENT->value)->first();
        $user->assignRole($role);

        // Obtener permisos existentes
        $allPermissions = Permission::where('type', 'model')->take(4)->get();
        $permissionNames = $allPermissions->pluck('name')->toArray();

        // Asignar algunos permisos primero
        $initialPermissions = [$permissionNames[0], $permissionNames[1]];
        $user->givePermissionTo($initialPermissions);

        // Plan: remover el primero, agregar el tercero y cuarto
        $permissionsToAdd = [$permissionNames[2], $permissionNames[3]];
        $permissionsToRemove = [$permissionNames[0]];

        $useCase = app(UpdatePermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $permissionsToAdd, $permissionsToRemove);

        // Assert
        $this->assertInstanceOf(PermissionsUpdatedToUserResponse::class, $result);
        $this->assertEquals(count($permissionsToAdd), count($result->permissions['permissionsAdded']));
        $this->assertEquals(count($permissionsToRemove), count($result->permissions['permissionsRemoved']));

        // Verificar que currentPermissions contiene los permisos correctos
        $currentPerms = $result->permissions['currentPermissions'];
        $this->assertContains($permissionNames[1], $currentPerms);  // Mantenido
        $this->assertContains($permissionNames[2], $currentPerms);  // Agregado
        $this->assertContains($permissionNames[3], $currentPerms);  // Agregado
        $this->assertNotContains($permissionNames[0], $currentPerms); // Removido

        // Verificar estado final en la base de datos
        $user->refresh();
        $this->assertFalse($user->hasPermissionTo($permissionNames[0]));
        $this->assertTrue($user->hasPermissionTo($permissionNames[1]));
        $this->assertTrue($user->hasPermissionTo($permissionNames[2]));
        $this->assertTrue($user->hasPermissionTo($permissionNames[3]));
    }

    #[Test]
    public function it_throws_exception_when_both_arrays_are_empty(): void
    {
        // Arrange
        $user = User::factory()->create();
        $useCase = app(UpdatePermissionsToSingleUserUseCase::class);

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Debe haber por lo menos un permiso para agregar o remover');

        // Act
        $useCase->execute($user->id, [], []);
    }

    #[Test]
    public function it_throws_exception_when_permissions_intersect(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Obtener un permiso existente
        $permission = Permission::where('type', 'model')->first();
        $permissionName = $permission->name;

        $permissionsToAdd = [$permissionName];
        $permissionsToRemove = [$permissionName]; // Mismo permiso en ambos arrays

        $useCase = app(UpdatePermissionsToSingleUserUseCase::class);

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Los siguientes permisos no pueden estar simultáneamente en agregar y remover: $permissionName");

        // Act
        $useCase->execute($user->id, $permissionsToAdd, $permissionsToRemove);
    }

    #[Test]
    public function it_throws_exception_with_multiple_intersecting_permissions(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Obtener permisos existentes
        $permissions = Permission::where('type', 'model')->take(3)->get();
        $permissionNames = $permissions->pluck('name')->toArray();

        $permissionsToAdd = $permissionNames;
        $permissionsToRemove = $permissionNames; // Mismos permisos en ambos arrays

        $useCase = app(UpdatePermissionsToSingleUserUseCase::class);

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Los siguientes permisos no pueden estar simultáneamente en agregar y remover');

        // Act
        $useCase->execute($user->id, $permissionsToAdd, $permissionsToRemove);
    }

    #[Test]
    public function it_only_adds_permissions_user_does_not_already_have(): void
    {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRoles::STUDENT->value)->first();
        $user->assignRole($role);

        // Obtener permisos y asignar algunos primero
        $permissions = Permission::where('type', 'model')->take(4)->get();
        $permissionNames = $permissions->pluck('name')->toArray();

        // Asignar 2 permisos inicialmente
        $initialPermissions = [$permissionNames[0], $permissionNames[1]];
        $user->givePermissionTo($initialPermissions);

        // Intentar agregar permisos (incluyendo algunos que ya tiene)
        $permissionsToAdd = $permissionNames; // Todos, incluidos los que ya tiene
        $permissionsToRemove = [];

        $useCase = app(UpdatePermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $permissionsToAdd, $permissionsToRemove);

        // Assert - Solo debería agregar los 2 que no tenía
        $this->assertInstanceOf(PermissionsUpdatedToUserResponse::class, $result);
        $this->assertEquals(2, count($result->permissions['permissionsAdded'])); // Solo 2 nuevos
        $this->assertEmpty($result->permissions['permissionsRemoved']);

        // Verificar que tiene todos los permisos ahora
        $user->refresh();
        foreach ($permissionNames as $permissionName) {
            $this->assertTrue($user->hasPermissionTo($permissionName));
        }
    }

    #[Test]
    public function it_only_removes_permissions_user_actually_has(): void
    {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRoles::STUDENT->value)->first();
        $user->assignRole($role);

        // Obtener permisos y asignar algunos primero
        $permissions = Permission::where('type', 'model')->take(4)->get();
        $permissionNames = $permissions->pluck('name')->toArray();

        // Asignar solo 2 permisos inicialmente
        $initialPermissions = [$permissionNames[0], $permissionNames[1]];
        $user->givePermissionTo($initialPermissions);

        // Intentar remover permisos (incluyendo algunos que no tiene)
        $permissionsToAdd = [];
        $permissionsToRemove = $permissionNames; // Todos, incluidos los que no tiene

        $useCase = app(UpdatePermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $permissionsToAdd, $permissionsToRemove);

        // Assert - Solo debería remover los 2 que tenía
        $this->assertInstanceOf(PermissionsUpdatedToUserResponse::class, $result);
        $this->assertEmpty($result->permissions['permissionsAdded']);
        $this->assertEquals(2, count($result->permissions['permissionsRemoved'])); // Solo 2 removidos

        // Verificar que no tiene ningún permiso ahora
        $user->refresh();
        foreach ($permissionNames as $permissionName) {
            $this->assertFalse($user->hasPermissionTo($permissionName));
        }
    }

    #[Test]
    public function it_throws_exception_when_user_does_not_exist(): void
    {
        // Arrange
        $nonExistentUserId = 99999;
        $permission = Permission::where('type', 'model')->first();

        $permissionsToAdd = [$permission->name];
        $permissionsToRemove = [];

        $useCase = app(UpdatePermissionsToSingleUserUseCase::class);

        // Assert - ModelNotFoundException será lanzada por findOrFail
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $useCase->execute($nonExistentUserId, $permissionsToAdd, $permissionsToRemove);
    }

    #[Test]
    public function it_returns_correct_full_name(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Carlos',
            'last_name' => 'García'
        ]);
        $role = Role::where('name', UserRoles::STUDENT->value)->first();
        $user->assignRole($role);

        $permission = Permission::where('type', 'model')->first();

        $permissionsToAdd = [$permission->name];
        $permissionsToRemove = [];

        $useCase = app(UpdatePermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $permissionsToAdd, $permissionsToRemove);

        // Assert
        $this->assertInstanceOf(PermissionsUpdatedToUserResponse::class, $result);
        $this->assertEquals('Carlos García', $result->fullName);
    }

    #[Test]
    public function it_returns_correct_current_permissions_list(): void
    {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRoles::STUDENT->value)->first();
        $user->assignRole($role);

        // Obtener permisos existentes
        $permissions = Permission::where('type', 'model')->take(3)->get();
        $permissionNames = $permissions->pluck('name')->toArray();

        // Asignar permiso inicial
        $user->givePermissionTo($permissionNames[0]);

        // Plan: agregar 1, remover 1
        $permissionsToAdd = [$permissionNames[1]];
        $permissionsToRemove = [$permissionNames[0]];

        $useCase = app(UpdatePermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $permissionsToAdd, $permissionsToRemove);

        // Assert
        $this->assertInstanceOf(PermissionsUpdatedToUserResponse::class, $result);

        // Verificar que currentPermissions contiene solo el permiso agregado
        $currentPermissions = $result->permissions['currentPermissions'];
        $this->assertCount(1, $currentPermissions);
        $this->assertContains($permissionNames[1], $currentPermissions);
        $this->assertNotContains($permissionNames[0], $currentPermissions);
    }


    #[Test]
    public function it_preserves_existing_permissions_not_in_add_or_remove(): void
    {
        // Arrange
        $user = User::factory()->create();
        $role = Role::where('name', UserRoles::STUDENT->value)->first();
        $user->assignRole($role);

        // Obtener permisos existentes
        $permissions = Permission::where('type', 'model')->take(4)->get();
        $permissionNames = $permissions->pluck('name')->toArray();

        // Asignar 3 permisos inicialmente
        $initialPermissions = [$permissionNames[0], $permissionNames[1], $permissionNames[2]];
        $user->givePermissionTo($initialPermissions);

        // Solo modificar algunos
        $permissionsToAdd = [$permissionNames[3]];
        $permissionsToRemove = [$permissionNames[0]];

        $useCase = app(UpdatePermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $permissionsToAdd, $permissionsToRemove);

        // Assert - Los permisos no modificados deben preservarse
        $user->refresh();
        $this->assertFalse($user->hasPermissionTo($permissionNames[0])); // Removido
        $this->assertTrue($user->hasPermissionTo($permissionNames[1]));  // Preservado
        $this->assertTrue($user->hasPermissionTo($permissionNames[2]));  // Preservado
        $this->assertTrue($user->hasPermissionTo($permissionNames[3]));  // Agregado

        // Verificar currentPermissions
        $currentPermissions = $result->permissions['currentPermissions'];
        $this->assertCount(3, $currentPermissions);
        $this->assertContains($permissionNames[1], $currentPermissions);
        $this->assertContains($permissionNames[2], $currentPermissions);
        $this->assertContains($permissionNames[3], $currentPermissions);
        $this->assertNotContains($permissionNames[0], $currentPermissions);
    }

    #[Test]
    public function it_handles_empty_name_or_last_name(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => '', // Nombre vacío
            'last_name' => 'Pérez'
        ]);
        $role = Role::where('name', UserRoles::STUDENT->value)->first();
        $user->assignRole($role);

        $permission = Permission::where('type', 'model')->first();

        $permissionsToAdd = [$permission->name];
        $permissionsToRemove = [];

        $useCase = app(UpdatePermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $permissionsToAdd, $permissionsToRemove);

        // Assert - Debería manejar nombres vacíos
        $this->assertInstanceOf(PermissionsUpdatedToUserResponse::class, $result);
        $this->assertEquals(' Pérez', $result->fullName); // Nota: espacio al inicio
    }

    #[Test]
    public function it_works_with_users_having_no_roles(): void
    {
        // Arrange
        $user = User::factory()->create();
        // No asignar ningún rol

        $permission = Permission::where('type', 'model')->first();

        $permissionsToAdd = [$permission->name];
        $permissionsToRemove = [];

        $useCase = app(UpdatePermissionsToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $permissionsToAdd, $permissionsToRemove);

        // Assert - Debería funcionar aunque no tenga roles
        $this->assertInstanceOf(PermissionsUpdatedToUserResponse::class, $result);
        $this->assertContains($permission->name, $result->permissions['permissionsAdded']);

        // Verificar que el permiso fue asignado
        $user->refresh();
        $this->assertTrue($user->hasPermissionTo($permission->name));
    }

}
