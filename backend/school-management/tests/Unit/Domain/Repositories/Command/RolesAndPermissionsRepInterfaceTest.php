<?php

namespace Tests\Unit\Domain\Repositories\Command;

use Tests\Stubs\Repositories\Command\RolesAndPermissionsRepStub;
use Tests\Unit\Domain\Repositories\BaseRepositoryTestCase;
use App\Core\Domain\Repositories\Command\Auth\RolesAndPermissionsRepInterface;
use App\Models\User;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;

class RolesAndPermissionsRepInterfaceTest extends BaseRepositoryTestCase
{
    /**
     * The interface class being implemented
     */
    protected string $interfaceClass = RolesAndPermissionsRepInterface::class;

    /**
     * Setup the repository instance for testing
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Usamos un stub para probar el contrato
        $this->repository = new RolesAndPermissionsRepStub();
    }

    /**
     * Test que el repositorio puede ser instanciado
     */
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertNotNull($this->repository, 'El repositorio no está inicializado');
        $this->assertImplementsInterface($this->interfaceClass);
    }

    /**
     * Test que todos los métodos requeridos existen
     */
    #[Test]
    public function it_has_all_required_methods(): void
    {
        $this->assertNotNull($this->repository, 'El repositorio no está inicializado');

        $methods = [
            'assignRoles',
            'givePermissionsByType',
            'removePermissions',
            'addPermissions',
            'syncRoles',
            'getUsersPermissions',
            'getUsersRoles'
        ];

        foreach ($methods as $method) {
            $this->assertMethodExists($method);
        }
    }

    #[Test]
    public function it_can_assign_roles(): void
    {
        $roleRows = [
            ['role_id' => 1, 'model_id' => 1, 'model_type' => User::class],
            ['role_id' => 2, 'model_id' => 2, 'model_type' => User::class],
        ];

        // No debería lanzar excepción
        $this->repository->assignRoles($roleRows);

        $this->addToAssertionCount(1); // Verificar que no se lanzó excepción
    }

    #[Test]
    public function assign_roles_does_nothing_with_empty_array(): void
    {
        // No debería lanzar excepción
        $this->repository->assignRoles([]);

        $this->addToAssertionCount(1); // Verificar que no se lanzó excepción
    }

    #[Test]
    public function it_can_give_permissions_by_type(): void
    {
        $user = $this->createMockUser();
        $belongsTo = 'school';
        $type = 'model';

        // No debería lanzar excepción
        $this->repository->givePermissionsByType($user, $belongsTo, $type);

        $this->addToAssertionCount(1); // Verificar que no se lanzó excepción
    }

    #[Test]
    public function give_permissions_uses_default_type(): void
    {
        $user = $this->createMockUser();
        $belongsTo = 'school';

        // No debería lanzar excepción sin especificar tipo
        $this->repository->givePermissionsByType($user, $belongsTo);

        $this->addToAssertionCount(1); // Verificar que no se lanzó excepción
    }

    #[Test]
    public function it_can_remove_permissions(): void
    {
        $userIds = [1, 2, 3];
        $permissionIds = [10, 11, 12];

        $result = $this->repository->removePermissions($userIds, $permissionIds);

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    #[Test]
    public function remove_permissions_returns_zero_with_empty_arrays(): void
    {
        $result = $this->repository->removePermissions([], [1, 2, 3]);
        $this->assertEquals(0, $result);

        $result2 = $this->repository->removePermissions([1, 2, 3], []);
        $this->assertEquals(0, $result2);

        $result3 = $this->repository->removePermissions([], []);
        $this->assertEquals(0, $result3);
    }

    #[Test]
    public function it_can_add_permissions(): void
    {
        $userIds = [1, 2];
        $permissionIds = [10, 11];

        $result = $this->repository->addPermissions($userIds, $permissionIds);

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    #[Test]
    public function add_permissions_returns_zero_with_empty_arrays(): void
    {
        $result = $this->repository->addPermissions([], [1, 2, 3]);
        $this->assertEquals(0, $result);

        $result2 = $this->repository->addPermissions([1, 2, 3], []);
        $this->assertEquals(0, $result2);

        $result3 = $this->repository->addPermissions([], []);
        $this->assertEquals(0, $result3);
    }

    #[Test]
    public function it_can_sync_roles(): void
    {
        $users = Collection::make([
            $this->createMockUser(['id' => 1]),
            $this->createMockUser(['id' => 2]),
        ]);

        $rolesToAddIds = [1, 2];
        $rolesToRemoveIds = [3, 4];

        $result = $this->repository->syncRoles($users, $rolesToAddIds, $rolesToRemoveIds);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('removed', $result);
        $this->assertArrayHasKey('added', $result);
        $this->assertArrayHasKey('users_affected', $result);

        $this->assertIsInt($result['removed']);
        $this->assertIsInt($result['added']);
        $this->assertIsInt($result['users_affected']);
    }

    #[Test]
    public function sync_roles_handles_empty_arrays(): void
    {
        $users = Collection::make([
            $this->createMockUser(['id' => 1]),
        ]);

        // Sin roles para agregar o quitar
        $result = $this->repository->syncRoles($users, [], []);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['removed']);
        $this->assertEquals(0, $result['added']);
        $this->assertEquals(0, $result['users_affected']);
    }

    #[Test]
    public function it_can_get_users_permissions(): void
    {
        $userIds = [1, 2, 3];
        $result = $this->repository->getUsersPermissions($userIds);

        $this->assertIsArray($result);

        // Verificar estructura esperada
        foreach ($userIds as $userId) {
            if (isset($result[$userId])) {
                $this->assertIsString($result[$userId]);
            }
        }
    }

    #[Test]
    public function get_users_permissions_returns_empty_array_with_no_users(): void
    {
        $result = $this->repository->getUsersPermissions([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_can_get_users_roles(): void
    {
        $userIds = [1, 2, 3];
        $result = $this->repository->getUsersRoles($userIds);

        $this->assertIsArray($result);

        // Verificar estructura esperada
        foreach ($userIds as $userId) {
            if (isset($result[$userId])) {
                $this->assertIsString($result[$userId]);
            }
        }
    }

    #[Test]
    public function get_users_roles_returns_empty_array_with_no_users(): void
    {
        $result = $this->repository->getUsersRoles([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function add_permissions_handles_duplicates(): void
    {
        $userIds = [1];
        $permissionIds = [10, 10, 11]; // Permiso 10 duplicado

        $result = $this->repository->addPermissions($userIds, $permissionIds);

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    #[Test]
    public function sync_roles_can_add_only(): void
    {
        $users = Collection::make([
            $this->createMockUser(['id' => 1]),
        ]);

        $rolesToAddIds = [1, 2];
        $rolesToRemoveIds = [];

        $result = $this->repository->syncRoles($users, $rolesToAddIds, $rolesToRemoveIds);

        $this->assertIsArray($result);
        $this->assertEquals(0, $result['removed']);
        $this->assertGreaterThanOrEqual(0, $result['added']);
    }

    #[Test]
    public function sync_roles_can_remove_only(): void
    {
        $users = Collection::make([
            $this->createMockUser(['id' => 1]),
        ]);

        $rolesToAddIds = [];
        $rolesToRemoveIds = [3, 4];

        $result = $this->repository->syncRoles($users, $rolesToAddIds, $rolesToRemoveIds);

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(0, $result['removed']);
        $this->assertEquals(0, $result['added']);
    }

    #[Test]
    public function it_handles_database_errors_gracefully(): void
    {
        $stub = new RolesAndPermissionsRepStub();
        $stub->shouldThrowDatabaseError(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database error');

        $stub->addPermissions([1], [1]);
    }

    /**
     * Helper para crear mock de User
     */
    private function createMockUser(array $attributes = []): User
    {
        $user = $this->createMock(User::class);

        $defaults = [
            'id' => 1,
            'email' => 'test@example.com',
            'name' => 'Test User',
        ];

        $attributes = array_merge($defaults, $attributes);

        foreach ($attributes as $key => $value) {
            if (method_exists($user, $key)) {
                $user->method($key)->willReturn($value);
            } else {
                $user->$key = $value;
            }
        }

        // Mock para givePermissionTo si es necesario
        if (method_exists($user, 'givePermissionTo')) {
            $user->method('givePermissionTo')->willReturn(null);
        }

        return $user;
    }
}
