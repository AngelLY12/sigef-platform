<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\DTO\Response\General\RolesUpdatedToUserResponse;
use App\Core\Application\UseCases\Admin\RolePermissionManagement\UpdateRolesToSingleUserUseCase;
use App\Core\Domain\Enum\User\UserRoles;
use App\Exceptions\Validation\ValidationException;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UpdateRolesToSingleUserUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        $this->seed(PermissionsSeeder::class);
    }

    #[Test]
    public function it_adds_roles_to_user_successfully(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'John',
            'last_name' => 'Doe'
        ]);

        // Asegurar que el usuario tiene rol unverified inicialmente
        $unverifiedRole = Role::where('name', UserRoles::UNVERIFIED->value)->first();
        $user->assignRole($unverifiedRole);

        $rolesToAdd = [UserRoles::STUDENT->value, UserRoles::PARENT->value];
        $rolesToRemove = [];

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);

        // Assert
        $this->assertInstanceOf(RolesUpdatedToUserResponse::class, $result);
        $this->assertEquals($user->id, $result->userId);
        $this->assertEquals('John Doe', $result->fullName);

        // Verificar estructura de roles
        $this->assertIsArray($result->roles);
        $this->assertArrayHasKey('rolesAdded', $result->roles);
        $this->assertArrayHasKey('rolesRemoved', $result->roles);
        $this->assertArrayHasKey('currentRoles', $result->roles);

        // Debería agregar 2 roles y remover UNVERIFIED automáticamente
        $this->assertCount(2, $result->roles['rolesAdded']);
        $this->assertContains(UserRoles::STUDENT->value, $result->roles['rolesAdded']);
        $this->assertContains(UserRoles::PARENT->value, $result->roles['rolesAdded']);

        // UNVERIFIED debería estar en roles removidos
        $this->assertContains(UserRoles::UNVERIFIED->value, $result->roles['rolesRemoved']);

        // Verificar en la base de datos
        $user->refresh();
        $this->assertTrue($user->hasRole(UserRoles::STUDENT->value));
        $this->assertTrue($user->hasRole(UserRoles::PARENT->value));
        $this->assertFalse($user->hasRole(UserRoles::UNVERIFIED->value));
    }

    #[Test]
    public function it_removes_roles_from_user_successfully(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Jane',
            'last_name' => 'Smith'
        ]);

        // Asignar varios roles inicialmente
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $adminRole = Role::where('name', UserRoles::ADMIN->value)->first();
        $user->assignRole([$studentRole, $parentRole, $adminRole]);

        $rolesToAdd = [];
        $rolesToRemove = [UserRoles::STUDENT->value, UserRoles::PARENT->value];

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);

        // Assert
        $this->assertInstanceOf(RolesUpdatedToUserResponse::class, $result);
        $this->assertEquals(count($rolesToRemove), count($result->roles['rolesRemoved']));
        $this->assertEmpty($result->roles['rolesAdded']);

        // Verificar en la base de datos
        $user->refresh();
        $this->assertFalse($user->hasRole(UserRoles::STUDENT->value));
        $this->assertFalse($user->hasRole(UserRoles::PARENT->value));
        $this->assertTrue($user->hasRole(UserRoles::ADMIN->value)); // Este no se removió
    }

    #[Test]
    public function it_adds_and_removes_roles_simultaneously(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Alice',
            'last_name' => 'Johnson'
        ]);

        // Roles iniciales
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $user->assignRole([$studentRole, $parentRole]);

        $rolesToAdd = [UserRoles::ADMIN->value, UserRoles::FINANCIAL_STAFF->value];
        $rolesToRemove = [UserRoles::STUDENT->value];

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);

        // Assert
        $this->assertInstanceOf(RolesUpdatedToUserResponse::class, $result);
        $this->assertEquals(2, count($result->roles['rolesAdded']));
        $this->assertEquals(1, count($result->roles['rolesRemoved']));

        // Verificar currentRoles
        $currentRoles = $result->roles['currentRoles'];
        $this->assertContains(UserRoles::PARENT->value, $currentRoles); // Mantenido
        $this->assertContains(UserRoles::ADMIN->value, $currentRoles); // Agregado
        $this->assertContains(UserRoles::FINANCIAL_STAFF->value, $currentRoles); // Agregado
        $this->assertNotContains(UserRoles::STUDENT->value, $currentRoles); // Removido

        // Verificar en base de datos
        $user->refresh();
        $this->assertFalse($user->hasRole(UserRoles::STUDENT->value));
        $this->assertTrue($user->hasRole(UserRoles::PARENT->value));
        $this->assertTrue($user->hasRole(UserRoles::ADMIN->value));
        $this->assertTrue($user->hasRole(UserRoles::FINANCIAL_STAFF->value));
    }

    #[Test]
    public function it_automatically_removes_unverified_role_when_present(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Solo asignar rol UNVERIFIED
        $unverifiedRole = Role::where('name', UserRoles::UNVERIFIED->value)->first();
        $user->assignRole($unverifiedRole);

        $rolesToAdd = [UserRoles::STUDENT->value];
        $rolesToRemove = [];

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);

        // Assert - UNVERIFIED debería removerse automáticamente
        $this->assertInstanceOf(RolesUpdatedToUserResponse::class, $result);
        $this->assertContains(UserRoles::STUDENT->value, $result->roles['rolesAdded']);
        $this->assertContains(UserRoles::UNVERIFIED->value, $result->roles['rolesRemoved']);

        $user->refresh();
        $this->assertTrue($user->hasRole(UserRoles::STUDENT->value));
        $this->assertFalse($user->hasRole(UserRoles::UNVERIFIED->value));
    }

    #[Test]
    public function it_does_not_remove_unverified_if_not_present(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Asignar rol STUDENT sin UNVERIFIED
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $user->assignRole($studentRole);

        $rolesToAdd = [UserRoles::PARENT->value];
        $rolesToRemove = [];

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);

        // Assert - UNVERIFIED no debería estar en removed si no estaba presente
        $this->assertInstanceOf(RolesUpdatedToUserResponse::class, $result);
        $this->assertNotContains(UserRoles::UNVERIFIED->value, $result->roles['rolesRemoved']);

        $user->refresh();
        $this->assertTrue($user->hasRole(UserRoles::STUDENT->value));
        $this->assertTrue($user->hasRole(UserRoles::PARENT->value));
        $this->assertFalse($user->hasRole(UserRoles::UNVERIFIED->value));
    }

    #[Test]
    public function it_throws_exception_when_both_arrays_are_empty(): void
    {
        // Arrange
        $user = User::factory()->create();
        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Debe haber por lo menos un rol para agregar o remover');

        // Act
        $useCase->execute($user->id, [], []);
    }

    #[Test]
    public function it_throws_exception_when_roles_intersect(): void
    {
        // Arrange
        $user = User::factory()->create();

        $rolesToAdd = [UserRoles::STUDENT->value];
        $rolesToRemove = [UserRoles::STUDENT->value]; // Mismo rol en ambos arrays

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("Los siguientes roles no pueden estar simultáneamente en add y remove: " . UserRoles::STUDENT->value);

        // Act
        $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);
    }

    #[Test]
    public function it_throws_exception_with_multiple_intersecting_roles(): void
    {
        // Arrange
        $user = User::factory()->create();

        $rolesToAdd = [UserRoles::STUDENT->value, UserRoles::PARENT->value, UserRoles::ADMIN->value];
        $rolesToRemove = [UserRoles::STUDENT->value, UserRoles::ADMIN->value]; // Intersección de 2 roles

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Assert
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Los siguientes roles no pueden estar simultáneamente en add y remove');

        // Act
        $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);
    }

    #[Test]
    public function it_only_adds_roles_user_does_not_already_have(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Asignar algunos roles inicialmente
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $user->assignRole([$studentRole, $parentRole]);

        // Intentar agregar roles (incluyendo algunos que ya tiene)
        $rolesToAdd = [
            UserRoles::STUDENT->value, // Ya lo tiene
            UserRoles::PARENT->value,  // Ya lo tiene
            UserRoles::ADMIN->value,   // Nuevo
            UserRoles::FINANCIAL_STAFF->value // Nuevo
        ];
        $rolesToRemove = [];

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);

        // Assert - Solo debería agregar los 2 nuevos
        $this->assertInstanceOf(RolesUpdatedToUserResponse::class, $result);
        $this->assertEquals(2, count($result->roles['rolesAdded'])); // Solo ADMIN y FINANCIAL_STAFF
        $this->assertContains(UserRoles::ADMIN->value, $result->roles['rolesAdded']);
        $this->assertContains(UserRoles::FINANCIAL_STAFF->value, $result->roles['rolesAdded']);
        $this->assertNotContains(UserRoles::STUDENT->value, $result->roles['rolesAdded']);
        $this->assertNotContains(UserRoles::PARENT->value, $result->roles['rolesAdded']);
    }

    #[Test]
    public function it_only_removes_roles_user_actually_has(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Asignar solo 2 roles inicialmente
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $user->assignRole([$studentRole, $parentRole]);

        // Intentar remover roles (incluyendo algunos que no tiene)
        $rolesToAdd = [];
        $rolesToRemove = [
            UserRoles::STUDENT->value, // Lo tiene
            UserRoles::PARENT->value,  // Lo tiene
            UserRoles::ADMIN->value,   // No lo tiene
            UserRoles::FINANCIAL_STAFF->value // No lo tiene
        ];

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);

        // Assert - Solo debería remover los 2 que tenía
        $this->assertInstanceOf(RolesUpdatedToUserResponse::class, $result);
        $this->assertEmpty($result->roles['rolesAdded']);
        $this->assertEquals(2, count($result->roles['rolesRemoved'])); // Solo STUDENT y PARENT
        $this->assertContains(UserRoles::STUDENT->value, $result->roles['rolesRemoved']);
        $this->assertContains(UserRoles::PARENT->value, $result->roles['rolesRemoved']);
        $this->assertNotContains(UserRoles::ADMIN->value, $result->roles['rolesRemoved']);
        $this->assertNotContains(UserRoles::FINANCIAL_STAFF->value, $result->roles['rolesRemoved']);
    }

    #[Test]
    public function it_throws_exception_when_user_does_not_exist(): void
    {
        // Arrange
        $nonExistentUserId = 99999;
        $rolesToAdd = [UserRoles::STUDENT->value];
        $rolesToRemove = [];

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Assert - ModelNotFoundException será lanzada por findOrFail
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $useCase->execute($nonExistentUserId, $rolesToAdd, $rolesToRemove);
    }

    #[Test]
    public function it_returns_correct_full_name(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'Carlos',
            'last_name' => 'García'
        ]);

        $unverifiedRole = Role::where('name', UserRoles::UNVERIFIED->value)->first();
        $user->assignRole($unverifiedRole);

        $rolesToAdd = [UserRoles::STUDENT->value];
        $rolesToRemove = [];

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);

        // Assert
        $this->assertInstanceOf(RolesUpdatedToUserResponse::class, $result);
        $this->assertEquals('Carlos García', $result->fullName);
    }

    #[Test]
    public function it_returns_correct_current_roles_list(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Asignar roles iniciales
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $adminRole = Role::where('name', UserRoles::ADMIN->value)->first();
        $user->assignRole([$studentRole, $parentRole, $adminRole]);

        // Plan: agregar 1, remover 1
        $rolesToAdd = [UserRoles::FINANCIAL_STAFF->value];
        $rolesToRemove = [UserRoles::STUDENT->value];

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);

        // Assert
        $this->assertInstanceOf(RolesUpdatedToUserResponse::class, $result);

        // Verificar que currentRoles contiene los roles correctos
        $currentRoles = $result->roles['currentRoles'];
        $this->assertContains(UserRoles::PARENT->value, $currentRoles); // Mantenido
        $this->assertContains(UserRoles::ADMIN->value, $currentRoles);  // Mantenido
        $this->assertContains(UserRoles::FINANCIAL_STAFF->value, $currentRoles); // Agregado
        $this->assertNotContains(UserRoles::STUDENT->value, $currentRoles); // Removido
        $this->assertNotContains(UserRoles::UNVERIFIED->value, $currentRoles); // Nunca estuvo
    }

    #[Test]
    public function it_handles_removing_unverified_when_adding_other_roles(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Asignar UNVERIFIED y otro rol
        $unverifiedRole = Role::where('name', UserRoles::UNVERIFIED->value)->first();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $user->assignRole([$unverifiedRole, $studentRole]);

        $rolesToAdd = [UserRoles::PARENT->value];
        $rolesToRemove = [];

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);

        // Assert - UNVERIFIED debería removerse aunque no esté en rolesToRemove
        $this->assertInstanceOf(RolesUpdatedToUserResponse::class, $result);
        $this->assertContains(UserRoles::PARENT->value, $result->roles['rolesAdded']);
        $this->assertContains(UserRoles::UNVERIFIED->value, $result->roles['rolesRemoved']);
        $this->assertNotContains(UserRoles::STUDENT->value, $result->roles['rolesAdded']); // Ya lo tenía
        $this->assertNotContains(UserRoles::STUDENT->value, $result->roles['rolesRemoved']); // No se removió

        // Verificar currentRoles
        $currentRoles = $result->roles['currentRoles'];
        $this->assertContains(UserRoles::STUDENT->value, $currentRoles);
        $this->assertContains(UserRoles::PARENT->value, $currentRoles);
        $this->assertNotContains(UserRoles::UNVERIFIED->value, $currentRoles);
    }

    #[Test]
    public function it_preserves_existing_roles_not_in_add_or_remove(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Asignar varios roles inicialmente
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $adminRole = Role::where('name', UserRoles::ADMIN->value)->first();
        $financialRole = Role::where('name', UserRoles::FINANCIAL_STAFF->value)->first();
        $user->assignRole([$studentRole, $parentRole, $adminRole, $financialRole]);

        // Solo modificar algunos
        $rolesToAdd = [UserRoles::SUPERVISOR->value];
        $rolesToRemove = [UserRoles::STUDENT->value];

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);

        // Assert - Los roles no modificados deben preservarse
        $user->refresh();
        $this->assertFalse($user->hasRole(UserRoles::STUDENT->value)); // Removido
        $this->assertTrue($user->hasRole(UserRoles::PARENT->value));   // Preservado
        $this->assertTrue($user->hasRole(UserRoles::ADMIN->value));    // Preservado
        $this->assertTrue($user->hasRole(UserRoles::FINANCIAL_STAFF->value)); // Preservado
        $this->assertTrue($user->hasRole(UserRoles::SUPERVISOR->value)); // Agregado

        // Verificar currentRoles
        $currentRoles = $result->roles['currentRoles'];
        $this->assertCount(4, $currentRoles);
        $this->assertContains(UserRoles::PARENT->value, $currentRoles);
        $this->assertContains(UserRoles::ADMIN->value, $currentRoles);
        $this->assertContains(UserRoles::FINANCIAL_STAFF->value, $currentRoles);
        $this->assertContains(UserRoles::SUPERVISOR->value, $currentRoles);
        $this->assertNotContains(UserRoles::STUDENT->value, $currentRoles);
    }

    #[Test]
    public function it_handles_all_user_roles_enum_values(): void
    {
        // Probar con todos los roles del enum
        $allRoles = [
            UserRoles::STUDENT,
            UserRoles::FINANCIAL_STAFF,
            UserRoles::PARENT,
            UserRoles::ADMIN,
            UserRoles::SUPERVISOR,
            UserRoles::APPLICANT,
        ];

        foreach ($allRoles as $roleToTest) {
            // Arrange
            $user = User::factory()->create();

            // Asignar UNVERIFIED inicialmente
            $unverifiedRole = Role::where('name', UserRoles::UNVERIFIED->value)->first();
            $user->assignRole($unverifiedRole);

            $rolesToAdd = [$roleToTest->value];
            $rolesToRemove = [];

            $useCase = app(UpdateRolesToSingleUserUseCase::class);

            // Act
            $result = $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);

            // Assert
            $this->assertInstanceOf(RolesUpdatedToUserResponse::class, $result);
            $this->assertContains($roleToTest->value, $result->roles['rolesAdded']);
            $this->assertContains(UserRoles::UNVERIFIED->value, $result->roles['rolesRemoved']);

            // Limpiar para siguiente iteración
            $user->delete();
        }
    }

    #[Test]
    public function it_removes_unverified_even_when_only_removing_other_roles(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Asignar UNVERIFIED y otros roles
        $unverifiedRole = Role::where('name', UserRoles::UNVERIFIED->value)->first();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $user->assignRole([$unverifiedRole, $studentRole, $parentRole]);

        // Solo remover un rol, no agregar ninguno
        $rolesToAdd = [];
        $rolesToRemove = [UserRoles::PARENT->value];

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);

        // Assert - UNVERIFIED debería removerse aunque no esté en rolesToRemove
        $this->assertInstanceOf(RolesUpdatedToUserResponse::class, $result);
        $this->assertContains(UserRoles::PARENT->value, $result->roles['rolesRemoved']);
        $this->assertContains(UserRoles::UNVERIFIED->value, $result->roles['rolesRemoved']);
        $this->assertEmpty($result->roles['rolesAdded']);

        // Verificar estado final
        $user->refresh();
        $this->assertTrue($user->hasRole(UserRoles::STUDENT->value)); // Mantenido
        $this->assertFalse($user->hasRole(UserRoles::PARENT->value)); // Removido explícitamente
        $this->assertFalse($user->hasRole(UserRoles::UNVERIFIED->value)); // Removido automáticamente
    }

    #[Test]
    public function it_handles_duplicate_role_names_in_same_array(): void
    {
        // Arrange
        $user = User::factory()->create();

        $unverifiedRole = Role::where('name', UserRoles::UNVERIFIED->value)->first();
        $user->assignRole($unverifiedRole);

        $rolesToAdd = [UserRoles::STUDENT->value, UserRoles::STUDENT->value]; // Duplicado
        $rolesToRemove = [];

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);

        // Assert - Debería manejar duplicados sin problema
        $this->assertInstanceOf(RolesUpdatedToUserResponse::class, $result);
        $this->assertEquals(1, count($result->roles['rolesAdded'])); // Solo uno agregado (único)
        $this->assertContains(UserRoles::STUDENT->value, $result->roles['rolesAdded']);
    }

    #[Test]
    public function it_returns_empty_arrays_when_no_changes_made(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Asignar rol STUDENT (sin UNVERIFIED)
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $user->assignRole($studentRole);

        // Intentar agregar un rol que ya tiene y remover uno que no tiene
        $rolesToAdd = [UserRoles::STUDENT->value]; // Ya lo tiene
        $rolesToRemove = [UserRoles::ADMIN->value]; // No lo tiene

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);

        // Assert - No debería haber cambios
        $this->assertInstanceOf(RolesUpdatedToUserResponse::class, $result);
        $this->assertEmpty($result->roles['rolesAdded']);
        $this->assertEmpty($result->roles['rolesRemoved']);
        $this->assertCount(1, $result->roles['currentRoles']);
        $this->assertContains(UserRoles::STUDENT->value, $result->roles['currentRoles']);
    }

    #[Test]
    public function it_handles_transaction_rollback_on_failure(): void
    {
        // Arrange
        $user = User::factory()->create();
        $unverifiedRole = Role::where('name', UserRoles::UNVERIFIED->value)->first();
        $user->assignRole($unverifiedRole);

        $rolesToAdd = [UserRoles::STUDENT->value, UserRoles::PARENT->value];
        $rolesToRemove = [];

        // Mock para simular un error durante la transacción
        DB::shouldReceive('transaction')
            ->once()
            ->andThrow(new \Exception('Database error'));

        $useCase = app(UpdateRolesToSingleUserUseCase::class);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        // Act
        $useCase->execute($user->id, $rolesToAdd, $rolesToRemove);

        // Verificar que no se realizaron cambios (rollback)
        $user->refresh();
        $this->assertFalse($user->hasRole(UserRoles::STUDENT->value));
        $this->assertFalse($user->hasRole(UserRoles::PARENT->value));
        $this->assertTrue($user->hasRole(UserRoles::UNVERIFIED->value));
    }

}
