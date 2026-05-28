<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\UseCases\Admin\RolePermissionManagement\FindPermissionByIdUseCase;
use App\Exceptions\NotFound\PermissionNotFoundException;
use App\Models\Permission;
use Database\Seeders\PermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission as PermissionModel;
use Tests\TestCase;

class FindPermissionByIdUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionsSeeder::class);
    }

    public function test_it_returns_permission_when_found(): void
    {
        // Arrange
        $useCase = app(FindPermissionByIdUseCase::class);

        // Obtener un ID existente de la base de datos
        $existingPermission = Permission::first();
        $permissionId = $existingPermission->id;

        // Act
        $result = $useCase->execute($permissionId);

        // Assert
        $this->assertInstanceOf(\App\Core\Domain\Entities\Permission::class, $result);
        $this->assertEquals($permissionId, $result->id);
        $this->assertEquals($existingPermission->name, $result->name);
    }

    public function test_it_returns_correct_permission_data(): void
    {
        // Arrange
        $useCase = app(FindPermissionByIdUseCase::class);

        // Tomar varios permisos existentes para probar
        $permissions = PermissionModel::take(3)->get();

        foreach ($permissions as $expectedPermission) {
            // Act
            $result = $useCase->execute($expectedPermission->id);

            // Assert
            $this->assertInstanceOf(\App\Core\Domain\Entities\Permission::class, $result);
            $this->assertEquals($expectedPermission->id, $result->id);
            $this->assertEquals($expectedPermission->name, $result->name);
            $this->assertEquals($expectedPermission->type, $result->type);
        }
    }

    public function test_it_throws_exception_when_permission_not_found(): void
    {
        // Arrange
        $useCase = app(FindPermissionByIdUseCase::class);

        // Obtener el ID más alto y sumar 1 para asegurar que no existe
        $maxId = PermissionModel::max('id');
        $nonExistentId = $maxId + 100;

        // Assert
        $this->expectException(PermissionNotFoundException::class);
        $this->expectExceptionMessage('No se encontro el permiso seleccionado');

        // Act
        $useCase->execute($nonExistentId);
    }

    public function test_it_throws_exception_for_invalid_id_zero(): void
    {
        // Arrange
        $useCase = app(FindPermissionByIdUseCase::class);

        // Assert
        $this->expectException(PermissionNotFoundException::class);

        // Act
        $useCase->execute(0);
    }

    public function test_it_throws_exception_for_negative_id(): void
    {
        // Arrange
        $useCase = app(FindPermissionByIdUseCase::class);

        // Assert
        $this->expectException(PermissionNotFoundException::class);

        // Act
        $useCase->execute(-1);
    }

    public function test_it_can_find_permission_after_deletion(): void
    {
        // Arrange
        $useCase = app(FindPermissionByIdUseCase::class);

        // Obtener dos permisos existentes
        $permissionToDelete = PermissionModel::first();
        $permissionToKeep = PermissionModel::where('id', '!=', $permissionToDelete->id)->first();

        // Act & Assert - Verificar que existe antes de eliminar
        $foundBeforeDelete = $useCase->execute($permissionToDelete->id);
        $this->assertEquals($permissionToDelete->id, $foundBeforeDelete->id);

        // Eliminar un permiso
        $permissionToDelete->delete();

        // Verificar que no se encuentra el eliminado
        $this->expectException(PermissionNotFoundException::class);
        $useCase->execute($permissionToDelete->id);

        // Verificar que otros permisos aún existen
        $foundPermission = $useCase->execute($permissionToKeep->id);
        $this->assertInstanceOf(Permission::class, $foundPermission);
        $this->assertEquals($permissionToKeep->id, $foundPermission->getId());
    }

    public function test_it_returns_complete_permission_object(): void
    {
        // Arrange
        $useCase = app(FindPermissionByIdUseCase::class);
        $existingPermission = PermissionModel::first();
        $permissionId = $existingPermission->id;

        // Act
        $result = $useCase->execute($permissionId);

        // Assert - Verificar que todos los métodos getter funcionan
        $this->assertIsInt($result->id);
        $this->assertIsString($result->name);

    }

    public function test_it_handles_database_isolation_correctly(): void
    {
        // Este test verifica que cada test tiene su propio estado de base de datos

        // Arrange
        $useCase = app(FindPermissionByIdUseCase::class);

        // En este punto, solo deberían existir los permisos creados por el seeder
        // Intentar encontrar un permiso que no existe
        $maxId = PermissionModel::max('id');
        $nonExistentId = $maxId + 100;

        $this->expectException(PermissionNotFoundException::class);

        // Act
        $useCase->execute($nonExistentId);
    }

    public function test_it_works_with_fresh_database(): void
    {
        // Limpiar la base de datos (RefreshDatabase ya lo hace, pero por si acaso)
        PermissionModel::query()->delete();

        // Arrange
        $useCase = app(FindPermissionByIdUseCase::class);

        // Assert
        $this->expectException(PermissionNotFoundException::class);

        // Act - Intentar encontrar cualquier permiso
        $useCase->execute(1);
    }

    public function test_it_preserves_permission_immutability(): void
    {
        // Arrange
        $useCase = app(FindPermissionByIdUseCase::class);
        $existingPermission = PermissionModel::first();
        $permissionId = $existingPermission->id;

        // Act - Obtener el permiso por primera vez
        $permission1 = $useCase->execute($permissionId);

        // Modificar el modelo directamente en la base de datos
        $newName = 'modified.' . uniqid();

        PermissionModel::where('id', $permissionId)->update([
            'name' => $newName,
        ]);

        // Obtener el permiso nuevamente
        $permission2 = $useCase->execute($permissionId);

        // Assert - El primer objeto debería mantener sus valores originales
        $this->assertEquals($existingPermission->name, $permission1->name);

        // El segundo objeto debería tener los nuevos valores
        $this->assertEquals($newName, $permission2->name);
    }

    public function test_it_finds_all_permissions_from_seeder(): void
    {
        // Arrange
        $useCase = app(FindPermissionByIdUseCase::class);

        // Obtener todos los permisos creados por el seeder
        $allPermissions = PermissionModel::all();

        // Assert - Verificar que podemos encontrar cada uno
        foreach ($allPermissions as $expectedPermission) {
            $foundPermission = $useCase->execute($expectedPermission->id);

            $this->assertInstanceOf(\App\Core\Domain\Entities\Permission::class, $foundPermission);
            $this->assertEquals($expectedPermission->id, $foundPermission->id);
            $this->assertEquals($expectedPermission->name, $foundPermission->name);
        }

        // Verificar que encontramos todos los permisos
        $this->assertCount($allPermissions->count(), $allPermissions);
    }

}
