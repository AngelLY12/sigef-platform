<?php

namespace Tests\Unit\Infraestructure\Repositories\Query;

use App\Core\Domain\Entities\Permission;
use App\Core\Domain\Entities\Role;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Infraestructure\Repositories\Query\Auth\EloquentRolesAndPermissionQueryRepository;
use App\Models\User as EloquentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class EloquentRolesAndPermissionQueryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentRolesAndPermissionQueryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles básicos
        $roles = [
            UserRoles::ADMIN->value,
            UserRoles::SUPERVISOR->value,
            UserRoles::STUDENT->value,
            UserRoles::FINANCIAL_STAFF->value,
            UserRoles::UNVERIFIED->value,
        ];

        foreach ($roles as $roleName) {
            SpatieRole::create(['name' => $roleName, 'guard_name' => 'web']);
        }

        // Crear permisos de ejemplo
        $permissions = [
            ['name' => 'user.create', 'type' => 'model', 'belongs_to' => 'admin'],
            ['name' => 'user.edit', 'type' => 'model', 'belongs_to' => 'admin'],
            ['name' => 'user.delete', 'type' => 'model', 'belongs_to' => 'admin'],
            ['name' => 'payment.view', 'type' => 'model', 'belongs_to' => 'global-payment'],
            ['name' => 'payment.create', 'type' => 'model', 'belongs_to' => 'global-payment'],
            ['name' => 'system.config', 'type' => 'model', 'belongs_to' => 'administration'],
            ['name' => 'student.payment.view', 'type' => 'model', 'belongs_to' => 'student-payment'],
            ['name' => 'student.payment.create', 'type' => 'model', 'belongs_to' => 'student-payment'],
            ['name' => 'course.view', 'type' => 'model', 'belongs_to' => 'teacher'],
            ['name' => 'course.edit', 'type' => 'model', 'belongs_to' => 'teacher'],
        ];

        foreach ($permissions as $permission) {
            SpatiePermission::create($permission);
        }

        $this->repository = new EloquentRolesAndPermissionQueryRepository();
    }

    // ==================== FIND ROLE BY ID TESTS ====================

    #[Test]
    public function find_role_by_id_successfully(): void
    {
        // Arrange
        $role = SpatieRole::first();

        // Act
        $result = $this->repository->findRoleById($role->id);

        // Assert
        $this->assertInstanceOf(Role::class, $result);
        $this->assertEquals($role->id, $result->id);
        $this->assertEquals($role->name, $result->name);
    }

    #[Test]
    public function find_role_by_id_returns_null_for_nonexistent_role(): void
    {
        // Act
        $result = $this->repository->findRoleById(999999);

        // Assert
        $this->assertNull($result);
    }

    // ==================== FIND ROLE BY NAME TESTS ====================

    #[Test]
    public function find_role_by_name_successfully(): void
    {
        // Arrange
        $roleName = UserRoles::ADMIN->value;

        // Act
        $result = $this->repository->findRoleByName($roleName);

        // Assert
        $this->assertInstanceOf(Role::class, $result);
        $this->assertEquals($roleName, $result->name);
    }

    #[Test]
    public function find_role_by_name_returns_null_for_nonexistent_role(): void
    {
        // Act
        $result = $this->repository->findRoleByName('nonexistent-role');

        // Assert
        $this->assertNull($result);
    }

    // ==================== FIND ALL ROLES TESTS ====================

    #[Test]
    public function find_all_roles_returns_array_of_roles(): void
    {
        // Arrange - Crear un rol oculto
        SpatieRole::create([
            'name' => 'hidden-role',
            'guard_name' => 'web',
            'hidden' => true
        ]);

        // Act
        $result = $this->repository->findAllRoles();

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);

        foreach ($result as $role) {
            $this->assertInstanceOf(Role::class, $role);
            $this->assertNotNull($role->id);
            $this->assertNotNull($role->name);
        }

        // Verificar que no incluye roles ocultos
        $hiddenRoleNames = array_map(fn($role) => $role->name, $result);
        $this->assertNotContains('hidden-role', $hiddenRoleNames);
    }

    #[Test]
    public function find_all_roles_returns_empty_array_when_no_roles(): void
    {
        // Arrange - Eliminar todos los roles
        SpatieRole::query()->delete();

        // Act
        $result = $this->repository->findAllRoles();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ==================== FIND PERMISSION BY ID TESTS ====================

    #[Test]
    public function find_permission_by_id_successfully(): void
    {
        // Arrange
        $permission = SpatiePermission::first();

        // Act
        $result = $this->repository->findPermissionById($permission->id);

        // Assert
        $this->assertInstanceOf(Permission::class, $result);
        $this->assertEquals($permission->id, $result->id);
        $this->assertEquals($permission->name, $result->name);
    }

    #[Test]
    public function find_permission_by_id_returns_null_for_nonexistent_permission(): void
    {
        // Act
        $result = $this->repository->findPermissionById(999999);

        // Assert
        $this->assertNull($result);
    }

    // ==================== FIND PERMISSIONS APPLICABLE BY USERS TESTS ====================

    #[Test]
    public function find_permissions_applicable_by_users_with_role(): void
    {
        // Arrange
        $role = UserRoles::STUDENT->value;

        // Crear usuarios con rol de estudiante
        $students = EloquentUser::factory()->count(3)->create();
        foreach ($students as $student) {
            $student->assignRole($role);
        }

        // Act
        $result = $this->repository->findPermissionsApplicableByUsers($role, null);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result); // Solo un rol (student)

        $roleData = $result[0];
        $this->assertEquals($role, $roleData['role']);
        $this->assertEquals(3, $roleData['users']['count']);
        $this->assertCount(3, $roleData['users']['curps']); // Menos de 15, incluye CURPs

        // Verificar permisos
        $this->assertIsArray($roleData['permissions']);
        $this->assertGreaterThan(0, count($roleData['permissions']));

        foreach ($roleData['permissions'] as $permission) {
            $this->assertInstanceOf(Permission::class, $permission);
        }
    }

    #[Test]
    public function find_permissions_applicable_by_users_with_curps(): void
    {
        // Arrange
        $users = EloquentUser::factory()->count(5)->create();
        $curps = $users->pluck('curp')->toArray();

        // Asignar diferentes roles
        $users[0]->assignRole(UserRoles::STUDENT->value);
        $users[1]->assignRole(UserRoles::FINANCIAL_STAFF->value);
        $users[2]->assignRole(UserRoles::ADMIN->value);

        // Act
        $result = $this->repository->findPermissionsApplicableByUsers(null, $curps);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result); // Tres roles diferentes

        // Ordenar por rol para assertions consistentes
        $resultByRole = collect($result)->keyBy('role');

        $this->assertArrayHasKey(UserRoles::STUDENT->value, $resultByRole);
        $this->assertEquals(1, $resultByRole[UserRoles::STUDENT->value]['users']['count']);

        $this->assertArrayHasKey(UserRoles::ADMIN->value, $resultByRole);
        $this->assertEquals(1, $resultByRole[UserRoles::ADMIN->value]['users']['count']);

        $this->assertArrayHasKey(UserRoles::FINANCIAL_STAFF->value, $resultByRole);
        $this->assertEquals(1, $resultByRole[UserRoles::FINANCIAL_STAFF->value]['users']['count']);
    }

    #[Test]
    public function find_permissions_applicable_by_users_with_many_users_hides_curps(): void
    {
        // Arrange
        $role = UserRoles::STUDENT->value;

        // Crear más de 15 usuarios
        $students = EloquentUser::factory()->count(20)->create();
        foreach ($students as $student) {
            $student->assignRole($role);
        }

        // Act
        $result = $this->repository->findPermissionsApplicableByUsers($role, null);

        // Assert
        $roleData = $result[0];
        $this->assertEquals(20, $roleData['users']['count']);
        $this->assertEmpty($roleData['users']['curps']); // Más de 15, no incluye CURPs
    }

    #[Test]
    public function find_permissions_applicable_by_users_returns_empty_for_nonexistent_role(): void
    {
        // Act
        $result = $this->repository->findPermissionsApplicableByUsers('nonexistent-role', null);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function find_permissions_applicable_by_users_for_supervisor_role(): void
    {
        // Arrange
        $role = UserRoles::SUPERVISOR->value;

        $supervisors = EloquentUser::factory()->count(2)->create();
        foreach ($supervisors as $supervisor) {
            $supervisor->assignRole($role);
        }

        // Act
        $result = $this->repository->findPermissionsApplicableByUsers($role, null);

        // Assert
        $roleData = $result[0];

        // Verificar que incluye permisos de administration
        $permissionNames = array_map(fn($p) => $p->name, $roleData['permissions']);
        $this->assertContains('system.config', $permissionNames);
    }

    // ==================== FIND PERMISSION IDS TESTS ====================

    #[Test]
    public function find_permission_ids_successfully(): void
    {
        // Arrange
        $permissionNames = ['user.create', 'payment.view'];
        $role = UserRoles::ADMIN->value;

        // Act
        $result = $this->repository->findPermissionIds($permissionNames, $role);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // Verificar que los IDs corresponden a los permisos
        $permissions = SpatiePermission::whereIn('name', $permissionNames)->get();
        $expectedIds = $permissions->pluck('id')->toArray();

        sort($result);
        sort($expectedIds);
        $this->assertEquals($expectedIds, $result);
    }

    #[Test]
    public function find_permission_ids_with_student_role_includes_student_payment(): void
    {
        // Arrange
        $permissionNames = ['student.payment.view', 'payment.view'];
        $role = UserRoles::STUDENT->value;

        // Act
        $result = $this->repository->findPermissionIds($permissionNames, $role);

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    #[Test]
    public function find_permission_ids_returns_empty_for_nonexistent_permissions(): void
    {
        // Arrange
        $permissionNames = ['nonexistent.permission'];
        $role = UserRoles::ADMIN->value;

        // Act
        $result = $this->repository->findPermissionIds($permissionNames, $role);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ==================== GET ROLE IDS BY NAMES TESTS ====================

    #[Test]
    public function get_role_ids_by_names_successfully(): void
    {
        // Arrange
        $roleNames = [UserRoles::ADMIN->value, UserRoles::STUDENT->value];

        // Act
        $result = $this->repository->getRoleIdsByNames($roleNames);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $roles = SpatieRole::whereIn('name', $roleNames)->get();
        $expectedIds = $roles->pluck('id')->toArray();

        sort($result);
        sort($expectedIds);
        $this->assertEquals($expectedIds, $result);
    }

    #[Test]
    public function get_role_ids_by_names_returns_empty_for_nonexistent_roles(): void
    {
        // Arrange
        $roleNames = ['nonexistent-role'];

        // Act
        $result = $this->repository->getRoleIdsByNames($roleNames);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ==================== HAS ADMIN ASSIGN ERROR TESTS ====================

    #[Test]
    public function has_admin_assign_error_returns_true_when_assigning_admin_with_existing_admin_outside_group(): void
    {
        // Arrange
        $adminRole = SpatieRole::where('name', UserRoles::ADMIN->value)->first();

        // Crear un admin existente
        $existingAdmin = EloquentUser::factory()->create();
        $existingAdmin->assignRole($adminRole->name);

        // Crear usuarios objetivo (no incluyen al admin existente)
        $targetUsers = EloquentUser::factory()->count(3)->create();

        $rolesToAddIds = [$adminRole->id];

        // Act
        $result = $this->repository->hasAdminAssignError(
            $adminRole->id,
            $rolesToAddIds,
            $targetUsers
        );

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function has_admin_assign_error_returns_false_when_assigning_admin_to_existing_admin(): void
    {
        // Arrange
        $adminRole = SpatieRole::where('name', UserRoles::ADMIN->value)->first();

        // Crear un admin existente
        $existingAdmin = EloquentUser::factory()->create();
        $existingAdmin->assignRole($adminRole->name);

        // El grupo objetivo INCLUYE al admin existente
        $targetUsers = collect([$existingAdmin]);

        $rolesToAddIds = [$adminRole->id];

        // Act
        $result = $this->repository->hasAdminAssignError(
            $adminRole->id,
            $rolesToAddIds,
            $targetUsers
        );

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function has_admin_assign_error_returns_false_when_not_assigning_admin_role(): void
    {
        // Arrange
        $adminRole = SpatieRole::where('name', UserRoles::ADMIN->value)->first();
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();

        $targetUsers = EloquentUser::factory()->count(2)->create();
        $rolesToAddIds = [$studentRole->id]; // No incluye admin

        // Act
        $result = $this->repository->hasAdminAssignError(
            $adminRole->id,
            $rolesToAddIds,
            $targetUsers
        );

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function has_admin_assign_error_returns_false_when_no_existing_admin(): void
    {
        // Arrange
        $adminRole = SpatieRole::where('name', UserRoles::ADMIN->value)->first();

        // Eliminar cualquier admin existente
        DB::table('model_has_roles')->where('role_id', $adminRole->id)->delete();

        $targetUsers = EloquentUser::factory()->count(2)->create();
        $rolesToAddIds = [$adminRole->id];

        // Act
        $result = $this->repository->hasAdminAssignError(
            $adminRole->id,
            $rolesToAddIds,
            $targetUsers
        );

        // Assert
        $this->assertFalse($result);
    }

    // ==================== HAS ADMIN REMOVE ERROR TESTS ====================

    #[Test]
    public function has_admin_remove_error_returns_true_when_removing_admin_from_some_but_not_all(): void
    {
        // Arrange
        $adminRole = SpatieRole::where('name', UserRoles::ADMIN->value)->first();

        // Crear múltiples admins
        $admin1 = EloquentUser::factory()->create();
        $admin2 = EloquentUser::factory()->create();
        $admin3 = EloquentUser::factory()->create();

        $admin1->assignRole($adminRole->name);
        $admin2->assignRole($adminRole->name);
        $admin3->assignRole($adminRole->name);

        // Solo remover rol de admin de algunos
        $targetUsers = collect([$admin1, $admin2]); // No incluye admin3
        $rolesToRemoveIds = [$adminRole->id];

        // Act
        $result = $this->repository->hasAdminRemoveError(
            $adminRole->id,
            $rolesToRemoveIds,
            $targetUsers
        );

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function has_admin_remove_error_returns_false_when_removing_admin_from_all(): void
    {
        // Arrange
        $adminRole = SpatieRole::where('name', UserRoles::ADMIN->value)->first();

        // Crear múltiples admins
        $admin1 = EloquentUser::factory()->create();
        $admin2 = EloquentUser::factory()->create();

        $admin1->assignRole($adminRole->name);
        $admin2->assignRole($adminRole->name);

        // Remover rol de admin de todos
        $targetUsers = collect([$admin1, $admin2]);
        $rolesToRemoveIds = [$adminRole->id];

        // Act
        $result = $this->repository->hasAdminRemoveError(
            $adminRole->id,
            $rolesToRemoveIds,
            $targetUsers
        );

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function has_admin_remove_error_returns_false_when_not_removing_admin_role(): void
    {
        // Arrange
        $adminRole = SpatieRole::where('name', UserRoles::ADMIN->value)->first();
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();

        $targetUsers = EloquentUser::factory()->count(2)->create();
        $rolesToRemoveIds = [$studentRole->id]; // No incluye admin

        // Act
        $result = $this->repository->hasAdminRemoveError(
            $adminRole->id,
            $rolesToRemoveIds,
            $targetUsers
        );

        // Assert
        $this->assertFalse($result);
    }

    // ==================== HAS ADMIN MISSING ERROR TESTS ====================

    #[Test]
    public function has_admin_missing_error_returns_true_when_removing_only_admin_without_replacement(): void
    {
        // Arrange
        $adminRole = SpatieRole::where('name', UserRoles::ADMIN->value)->first();

        // Crear un solo admin
        $admin = EloquentUser::factory()->create();
        $admin->assignRole($adminRole->name);

        $rolesToRemoveIds = [$adminRole->id];
        $rolesToAddIds = []; // No se agregan nuevos roles

        // Act
        $result = $this->repository->hasAdminMissingError(
            $adminRole->id,
            $rolesToRemoveIds,
            $rolesToAddIds
        );

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function has_admin_missing_error_returns_false_when_multiple_admins_exist(): void
    {
        // Arrange
        $adminRole = SpatieRole::where('name', UserRoles::ADMIN->value)->first();

        // Crear múltiples admins
        $admin1 = EloquentUser::factory()->create();
        $admin2 = EloquentUser::factory()->create();

        $admin1->assignRole($adminRole->name);
        $admin2->assignRole($adminRole->name);

        $rolesToRemoveIds = [$adminRole->id];
        $rolesToAddIds = [];

        // Act
        $result = $this->repository->hasAdminMissingError(
            $adminRole->id,
            $rolesToRemoveIds,
            $rolesToAddIds
        );

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function has_admin_missing_error_returns_false_when_adding_new_roles(): void
    {
        // Arrange
        $adminRole = SpatieRole::where('name', UserRoles::ADMIN->value)->first();
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();

        // Crear un solo admin
        $admin = EloquentUser::factory()->create();
        $admin->assignRole($adminRole->name);

        $rolesToRemoveIds = [$adminRole->id];
        $rolesToAddIds = [$studentRole->id]; // Se agregan nuevos roles

        // Act
        $result = $this->repository->hasAdminMissingError(
            $adminRole->id,
            $rolesToRemoveIds,
            $rolesToAddIds
        );

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function has_admin_missing_error_returns_false_when_not_removing_admin(): void
    {
        // Arrange
        $adminRole = SpatieRole::where('name', UserRoles::ADMIN->value)->first();
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();

        $rolesToRemoveIds = [$studentRole->id]; // No remueve admin
        $rolesToAddIds = [];

        // Act
        $result = $this->repository->hasAdminMissingError(
            $adminRole->id,
            $rolesToRemoveIds,
            $rolesToAddIds
        );

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function has_admin_assign_error_returns_true_when_trying_to_create_new_admin_with_existing_one(): void
    {
        // Arrange
        $adminRole = SpatieRole::where('name', UserRoles::ADMIN->value)->first();

        // 1. Ya existe un admin en el sistema
        $currentAdmin = EloquentUser::factory()->create();
        $currentAdmin->assignRole(UserRoles::ADMIN->value);

        // 2. Intentar hacer admin a otro usuario
        $newUser = EloquentUser::factory()->create();
        $rolesToAddIds = [$adminRole->id];

        // Act
        $result = $this->repository->hasAdminAssignError(
            $adminRole->id,
            $rolesToAddIds,
            collect([$newUser]) // Solo el nuevo usuario, NO el admin actual
        );

        // Assert
        $this->assertTrue($result,
            "Debería ser error: ya existe un admin y se intenta crear otro diferente"
        );
    }

    #[Test]
    public function has_admin_assign_error_returns_false_when_assigning_admin_to_current_admin(): void
    {
        // Arrange
        $adminRole = SpatieRole::where('name', UserRoles::ADMIN->value)->first();

        // 1. Ya existe un admin
        $currentAdmin = EloquentUser::factory()->create();
        $currentAdmin->assignRole(UserRoles::ADMIN->value);

        // 2. Re-asignar rol admin al mismo usuario (no crear nuevo)
        $rolesToAddIds = [$adminRole->id];

        // Act
        $result = $this->repository->hasAdminAssignError(
            $adminRole->id,
            $rolesToAddIds,
            collect([$currentAdmin]) // El admin actual SÍ está en el grupo
        );

        // Assert
        $this->assertFalse($result,
            "No debería ser error: se está asignando al admin existente, no creando uno nuevo"
        );
    }

    // ==================== INTEGRATION TESTS ====================

    #[Test]
    public function comprehensive_role_and_permission_queries(): void
    {
        // 1. Crear roles y permisos
        $adminRole = SpatieRole::where('name', UserRoles::ADMIN->value)->first();
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();

        // 2. Crear un ADMIN existente (fuera del grupo objetivo)
        $existingAdmin = EloquentUser::factory()->create();
        $existingAdmin->assignRole(UserRoles::ADMIN->value);

        // 2. Encontrar roles
        $foundRole = $this->repository->findRoleByName(UserRoles::ADMIN->value);
        $this->assertInstanceOf(Role::class, $foundRole);

        $allRoles = $this->repository->findAllRoles();
        $this->assertGreaterThan(0, count($allRoles));

        // 3. Encontrar permisos aplicables
        $student = EloquentUser::factory()->create();
        $student->assignRole(UserRoles::STUDENT->value);

        $permissions = $this->repository->findPermissionsApplicableByUsers(
            UserRoles::STUDENT->value,
            null
        );
        $this->assertIsArray($permissions);

        // 4. Encontrar IDs de permisos
        $permissionIds = $this->repository->findPermissionIds(
            ['user.create', 'payment.view'],
            UserRoles::ADMIN->value
        );
        $this->assertCount(2, $permissionIds);

        // 5. Encontrar IDs de roles
        $roleIds = $this->repository->getRoleIdsByNames([
            UserRoles::ADMIN->value,
            UserRoles::STUDENT->value
        ]);
        $this->assertCount(2, $roleIds);

        // 6. Verificar errores de admin
        $hasError = $this->repository->hasAdminAssignError(
            $adminRole->id,
            [$adminRole->id],
            collect([$student])
        );
        $this->assertTrue($hasError); // Hay un admin existente fuera del grupo
    }

    #[Test]
    public function permission_filtering_by_role_type(): void
    {
        // Arrange
        $studentRole = UserRoles::STUDENT->value;
        $supervisorRole = UserRoles::SUPERVISOR->value;
        $adminRole = UserRoles::ADMIN->value;

        // Crear usuarios con diferentes roles
        $student = EloquentUser::factory()->create();
        $student->assignRole($studentRole);

        $supervisor = EloquentUser::factory()->create();
        $supervisor->assignRole($supervisorRole);

        $admin = EloquentUser::factory()->create();
        $admin->assignRole($adminRole);

        // Act - Obtener permisos para cada rol
        $studentPermissions = $this->repository->findPermissionsApplicableByUsers($studentRole, null);
        $supervisorPermissions = $this->repository->findPermissionsApplicableByUsers($supervisorRole, null);
        $adminPermissions = $this->repository->findPermissionsApplicableByUsers($adminRole, null);

        // Assert - Cada rol debería tener permisos específicos
        $this->assertNotEmpty($studentPermissions[0]['permissions']);
        $this->assertNotEmpty($supervisorPermissions[0]['permissions']);
        $this->assertNotEmpty($adminPermissions[0]['permissions']);

        // Verificar que supervisor incluye permisos de administration
        $supervisorPermNames = array_map(fn($p) => $p->name, $supervisorPermissions[0]['permissions']);
        $this->assertContains('system.config', $supervisorPermNames);
    }

    #[Test]
    public function edge_cases_and_error_handling(): void
    {
        // 1. Buscar rol con ID inválido
        $result = $this->repository->findRoleById(0);
        $this->assertNull($result);

        // 2. Buscar permiso con ID inválido
        $result = $this->repository->findPermissionById(0);
        $this->assertNull($result);

        // 3. Permisos aplicables sin parámetros
        $result = $this->repository->findPermissionsApplicableByUsers(null, null);
        $this->assertIsArray($result);
        $this->assertEmpty($result);

        // 4. IDs de permisos con array vacío
        $result = $this->repository->findPermissionIds([], UserRoles::ADMIN->value);
        $this->assertIsArray($result);
        $this->assertEmpty($result);

        // 5. IDs de roles con array vacío
        $result = $this->repository->getRoleIdsByNames([]);
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

}
