<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\UseCases\Admin\RolePermissionManagement\FindAllRolesUseCase;
use App\Core\Domain\Enum\User\UserRoles;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FindAllRolesUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
    }


    public function test_it_returns_all_non_hidden_roles(): void
    {
        // Arrange
        $useCase = app(FindAllRolesUseCase::class);

        // Act
        $result = $useCase->execute();

        $expectedCount = count(UserRoles::cases()) - 1; // Todos menos ADMIN
        $this->assertCount($expectedCount, $result);

        // Verificar estructura básica
        foreach ($result as $role) {
            $this->assertInstanceOf(\App\Core\Domain\Entities\Role::class, $role);
            $this->assertIsInt($role->id);
            $this->assertIsString($role->name);
        }

        // Verificar que incluye todos los roles excepto ADMIN
        $expectedRoles = array_filter(
            UserRoles::values(),
            fn($role) => $role !== UserRoles::ADMIN->value
        );

        $actualRoles = array_map(fn($role) => $role->name,$result);
        sort($expectedRoles);
        sort($actualRoles);

        $this->assertEquals($expectedRoles, $actualRoles);
    }

    public function test_it_excludes_admin_role(): void
    {
        // Arrange
        $useCase = app(FindAllRolesUseCase::class);

        // Act
        $result = $useCase->execute();

        // Assert - No debe incluir 'admin'
        $roleNames = array_column($result, 'name');
        $this->assertNotContains(UserRoles::ADMIN->value, $roleNames);

        // Debería incluir todos los demás
        $otherRoles = array_filter(
            UserRoles::values(),
            fn($role) => $role !== UserRoles::ADMIN->value
        );

        foreach ($otherRoles as $expectedRole) {
            $this->assertContains($expectedRole, $roleNames);
        }
    }

    public function test_it_returns_empty_when_no_roles_exist(): void
    {
        // Arrange - Limpiar la base de datos primero
        Role::query()->delete();
        $useCase = app(FindAllRolesUseCase::class);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_it_returns_roles_in_expected_structure_from_mapper(): void
    {
        // Arrange
        $useCase = app(FindAllRolesUseCase::class);

        // Act
        $result = $useCase->execute();

        // Assert - Verificar que el mapper funciona correctamente
        $this->assertGreaterThan(0, count($result));

        // Tomar el primer rol como muestra
        $sampleRole = $result[0];

        // Verificar que es un objeto con métodos getter
        $this->assertIsInt($sampleRole->id);
        $this->assertIsString($sampleRole->name);
    }
    public function test_it_only_includes_roles_with_hidden_false(): void
    {
        // Arrange
        $useCase = app(FindAllRolesUseCase::class);

        // Act
        $result = $useCase->execute();

        $nonHiddenRolesInDb = Role::where('hidden', false)
            ->pluck('name')
            ->sort()
            ->values()
            ->toArray();

        $rolesInResult = array_column($result, 'name');
        sort($rolesInResult);

        $this->assertEquals($nonHiddenRolesInDb, $rolesInResult);
    }

    public function test_it_returns_correct_number_of_roles(): void
    {
        // Arrange
        $useCase = app(FindAllRolesUseCase::class);

        // Contar roles no-hidden según el enum
        $allRoles = UserRoles::values();
        $nonHiddenRoles = array_filter($allRoles, fn($role) => $role !== UserRoles::ADMIN->value);
        $expectedCount = count($nonHiddenRoles);

        // Act
        $result = $useCase->execute();

        // Assert
        $this->assertCount($expectedCount, $result);
    }

}
