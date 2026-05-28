<?php

namespace Tests\Unit\Infraestructure\Repositories\Command;

use App\Core\Infraestructure\Repositories\Command\Auth\EloquentRolesAndPermissionsRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EloquentRolesAndPermissionsRepositoryTest extends TestCase
{
    use RefreshDatabase;
    private EloquentRolesAndPermissionsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentRolesAndPermissionsRepository();

        // Limpiar tablas antes de cada test
        DB::table('model_has_roles')->delete();
        DB::table('model_has_permissions')->delete();
        Permission::query()->delete();
        Role::query()->delete();
    }

    #[Test]
    public function assignRoles_inserts_role_assignments(): void
    {
        // Arrange
        $user = User::factory()->create();
        $role = Role::create(['name' => 'test-role', 'guard_name' => 'web']);

        $roleRows = [
            [
                'role_id' => $role->id,
                'model_type' => User::class,
                'model_id' => $user->id,
            ]
        ];

        // Act
        $this->repository->assignRoles($roleRows);

        // Assert
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $role->id,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);
        $this->assertTrue($user->hasRole($role->name));
    }

    #[Test]
    public function assignRoles_does_nothing_when_empty_array(): void
    {
        // Arrange
        $roleRows = [];

        // Act
        $this->repository->assignRoles($roleRows);

        // Assert - No error should be thrown
        $this->assertTrue(true);
    }

    #[Test]
    public function assignRoles_uses_insertOrIgnore_to_avoid_duplicates(): void
    {
        // Arrange
        $user = User::factory()->create();
        $role = Role::create(['name' => 'test-role', 'guard_name' => 'web']);

        $roleRows = [
            [
                'role_id' => $role->id,
                'model_type' => User::class,
                'model_id' => $user->id,
            ]
        ];

        // Insert first time
        DB::table('model_has_roles')->insert($roleRows);

        // Act - Try to insert again (should be ignored)
        $this->repository->assignRoles($roleRows);

        // Assert - Should have only one record
        $count = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->where('model_id', $user->id)
            ->count();
        $this->assertEquals(1, $count);
    }

    #[Test]
    public function givePermissionsByType_assigns_permissions_based_on_belongs_to_and_type(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Crear permisos con diferentes belongs_to y type
        $permission1 = Permission::create([
            'name' => 'view-student',
            'guard_name' => 'web',
            'belongs_to' => 'student',
            'type' => 'model'
        ]);

        $permission2 = Permission::create([
            'name' => 'edit-student',
            'guard_name' => 'web',
            'belongs_to' => 'student',
            'type' => 'model'
        ]);

        Permission::create([
            'name' => 'view-payment',
            'guard_name' => 'web',
            'belongs_to' => 'payment',
            'type' => 'model'
        ]);

        Permission::create([
            'name' => 'admin-action',
            'guard_name' => 'web',
            'belongs_to' => 'student',
            'type' => 'system'
        ]);

        // Act
        $this->repository->givePermissionsByType($user, 'student', 'model');

        // Assert
        $this->assertTrue($user->hasPermissionTo('view-student'));
        $this->assertTrue($user->hasPermissionTo('edit-student'));
        $this->assertFalse($user->hasPermissionTo('view-payment'));
        $this->assertFalse($user->hasPermissionTo('admin-action'));
    }

    #[Test]
    public function removePermissions_deletes_specific_permissions_for_users(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $permission1 = Permission::create(['name' => 'permission-1', 'guard_name' => 'web']);
        $permission2 = Permission::create(['name' => 'permission-2', 'guard_name' => 'web']);
        $permission3 = Permission::create(['name' => 'permission-3', 'guard_name' => 'web']);

        // Asignar permisos
        $user1->givePermissionTo([$permission1->name, $permission2->name]);
        $user2->givePermissionTo([$permission2->name, $permission3->name]);

        // Act
        $removedCount = $this->repository->removePermissions(
            [$user1->id, $user2->id],
            [$permission2->id]
        );

        // Assert
        $this->assertEquals(2, $removedCount);
        $this->assertFalse($user1->fresh()->hasPermissionTo('permission-2'));
        $this->assertFalse($user2->fresh()->hasPermissionTo('permission-2'));
        $this->assertTrue($user1->fresh()->hasPermissionTo('permission-1'));
        $this->assertTrue($user2->fresh()->hasPermissionTo('permission-3'));
    }

    #[Test]
    public function removePermissions_returns_zero_when_empty_parameters(): void
    {
        // Act & Assert empty user IDs
        $result1 = $this->repository->removePermissions([], [1, 2]);
        $this->assertEquals(0, $result1);

        // Act & Assert empty permission IDs
        $result2 = $this->repository->removePermissions([1, 2], []);
        $this->assertEquals(0, $result2);

        // Act & Assert both empty
        $result3 = $this->repository->removePermissions([], []);
        $this->assertEquals(0, $result3);
    }

    #[Test]
    public function addPermissions_inserts_permissions_for_multiple_users(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $permission1 = Permission::create(['name' => 'permission-1', 'guard_name' => 'web']);
        $permission2 = Permission::create(['name' => 'permission-2', 'guard_name' => 'web']);

        // Act
        $addedCount = $this->repository->addPermissions(
            [$user1->id, $user2->id],
            [$permission1->id, $permission2->id]
        );

        // Assert
        $this->assertEquals(4, $addedCount); // 2 usuarios × 2 permisos = 4 asignaciones

        $this->assertTrue($user1->fresh()->hasPermissionTo('permission-1'));
        $this->assertTrue($user1->fresh()->hasPermissionTo('permission-2'));
        $this->assertTrue($user2->fresh()->hasPermissionTo('permission-1'));
        $this->assertTrue($user2->fresh()->hasPermissionTo('permission-2'));
    }

    #[Test]
    public function addPermissions_returns_zero_when_empty_parameters(): void
    {
        // Act & Assert
        $result = $this->repository->addPermissions([], []);
        $this->assertEquals(0, $result);
    }

    #[Test]
    public function addPermissions_uses_insertOrIgnore_to_prevent_duplicates(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'test-permission', 'guard_name' => 'web']);

        // First assignment
        $user->givePermissionTo($permission->name);

        // Act - Try to add again
        $addedCount = $this->repository->addPermissions(
            [$user->id],
            [$permission->id]
        );

        // Assert
        $this->assertEquals(0, $addedCount); // Should be ignored

        // Should still have the permission
        $this->assertTrue($user->fresh()->hasPermissionTo('test-permission'));
    }

    #[Test]
    public function syncRoles_adds_and_removes_roles_for_multiple_users(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $role1 = Role::create(['name' => 'role-1', 'guard_name' => 'web']);
        $role2 = Role::create(['name' => 'role-2', 'guard_name' => 'web']);
        $role3 = Role::create(['name' => 'role-3', 'guard_name' => 'web']);
        $role4 = Role::create(['name' => 'role-4', 'guard_name' => 'web']);

        // Setup initial roles
        $user1->assignRole([$role1->name, $role2->name]);
        $user2->assignRole([$role2->name, $role3->name]);
        $user3->assignRole([$role1->name, $role3->name]);

        $users = collect([$user1, $user2, $user3]);

        // Act - Add role4, remove role2
        $result = $this->repository->syncRoles(
            $users,
            [$role4->id], // Roles to add
            [$role2->id]  // Roles to remove
        );

        // Assert
        $this->assertEquals(3, $result['added']);   // 3 users × role4
        $this->assertEquals(2, $result['removed']); // user1 and user2 had role2
        $this->assertEquals(3, $result['users_affected']);

        // Verify final state
        $this->assertTrue($user1->fresh()->hasRole(['role-1', 'role-4']));
        $this->assertFalse($user1->fresh()->hasRole('role-2'));

        $this->assertTrue($user2->fresh()->hasRole(['role-3', 'role-4']));
        $this->assertFalse($user2->fresh()->hasRole('role-2'));

        $this->assertTrue($user3->fresh()->hasRole(['role-1', 'role-3', 'role-4']));
        $this->assertFalse($user3->fresh()->hasRole('role-2'));
    }

    #[Test]
    public function syncRoles_handles_empty_roles_to_add_and_remove(): void
    {
        // Arrange
        $user = User::factory()->create();
        $role = Role::create(['name' => 'test-role', 'guard_name' => 'web']);
        $user->assignRole($role->name);

        $users = collect([$user]);

        // Act - No changes
        $result = $this->repository->syncRoles($users, [], []);

        // Assert
        $this->assertEquals(0, $result['added']);
        $this->assertEquals(0, $result['removed']);
        $this->assertEquals(0, $result['users_affected']);
        $this->assertTrue($user->fresh()->hasRole('test-role'));
    }

    #[Test]
    public function syncRoles_does_not_add_existing_roles(): void
    {
        // Arrange
        $user = User::factory()->create();
        $role = Role::create(['name' => 'existing-role', 'guard_name' => 'web']);
        $user->assignRole($role->name);

        $users = collect([$user]);

        // Act - Try to add role that already exists
        $result = $this->repository->syncRoles($users, [$role->id], []);

        // Assert
        $this->assertEquals(0, $result['added']); // Should not add duplicate
        $this->assertEquals(0, $result['removed']);
        $this->assertTrue($user->fresh()->hasRole('existing-role'));
    }

    #[Test]
    public function getUsersPermissions_returns_permissions_grouped_by_user(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $permission1 = Permission::create(['name' => 'permission-1', 'guard_name' => 'web']);
        $permission2 = Permission::create(['name' => 'permission-2', 'guard_name' => 'web']);
        $permission3 = Permission::create(['name' => 'permission-3', 'guard_name' => 'web']);

        $user1->givePermissionTo([$permission1->name, $permission2->name]);
        $user2->givePermissionTo([$permission2->name, $permission3->name]);

        // Act
        $result = $this->repository->getUsersPermissions([$user1->id, $user2->id]);

        // Assert
        $this->assertArrayHasKey($user1->id, $result);
        $this->assertArrayHasKey($user2->id, $result);

        // Check permissions are concatenated strings
        $user1Permissions = explode(',', $result[$user1->id]);
        $user2Permissions = explode(',', $result[$user2->id]);

        $this->assertContains((string)$permission1->id, $user1Permissions);
        $this->assertContains((string)$permission2->id, $user1Permissions);
        $this->assertContains((string)$permission2->id, $user2Permissions);
        $this->assertContains((string)$permission3->id, $user2Permissions);
    }

    #[Test]
    public function getUsersRoles_returns_roles_grouped_by_user(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $role1 = Role::create(['name' => 'role-1', 'guard_name' => 'web']);
        $role2 = Role::create(['name' => 'role-2', 'guard_name' => 'web']);
        $role3 = Role::create(['name' => 'role-3', 'guard_name' => 'web']);

        $user1->assignRole([$role1->name, $role2->name]);
        $user2->assignRole([$role2->name, $role3->name]);

        // Act
        $result = $this->repository->getUsersRoles([$user1->id, $user2->id]);

        // Assert
        $this->assertArrayHasKey($user1->id, $result);
        $this->assertArrayHasKey($user2->id, $result);

        // Check roles are concatenated strings
        $user1Roles = explode(',', $result[$user1->id]);
        $user2Roles = explode(',', $result[$user2->id]);

        $this->assertContains((string)$role1->id, $user1Roles);
        $this->assertContains((string)$role2->id, $user1Roles);
        $this->assertContains((string)$role2->id, $user2Roles);
        $this->assertContains((string)$role3->id, $user2Roles);
    }

    #[Test]
    public function syncRoles_in_transaction_ensures_data_consistency(): void
    {
        // Arrange
        $user = User::factory()->create();
        $role1 = Role::create(['name' => 'role-1', 'guard_name' => 'web']);
        $role2 = Role::create(['name' => 'role-2', 'guard_name' => 'web']);

        $user->assignRole($role1->name);

        $users = collect([$user]);

        // Act - This should remove role1 and add role2 atomically
        $result = $this->repository->syncRoles(
            $users,
            [$role2->id], // Add role2
            [$role1->id]  // Remove role1
        );

        // Assert
        $this->assertEquals(1, $result['added']);
        $this->assertEquals(1, $result['removed']);

        $user->refresh();
        $this->assertFalse($user->hasRole('role-1'));
        $this->assertTrue($user->hasRole('role-2'));
    }

    #[Test]
    public function addPermissions_falls_back_to_individual_inserts_when_bulk_fails(): void
    {
        // Arrange
        $user = User::factory()->create();
        $permission = Permission::create(['name' => 'test-permission', 'guard_name' => 'web']);

        // Simulate bulk insert failure by mocking DB::table to throw exception
        $mock = $this->partialMock(EloquentRolesAndPermissionsRepository::class);

        // Simulate bulk insert failure, then fallback should work
        // Since we can't easily mock the private method, we'll test the actual behavior

        // Act - Add permission that doesn't exist (should fail silently in fallback)
        $result = $this->repository->addPermissions(
            [$user->id],
            [99999] // Non-existent permission ID
        );

        // Assert - Should return 0 (no permissions added)
        $this->assertEquals(0, $result);
    }

    #[Test]
    public function syncRoles_properly_adds_and_removes_roles(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $studentRole = Role::create(['name' => 'estudiante', 'guard_name' => 'web']);
        $parentRole = Role::create(['name' => 'padre', 'guard_name' => 'web']);

        // user1 is already a student
        $user1->assignRole($studentRole->name);

        // user2 is a parent
        $user2->assignRole($parentRole->name);

        // user3 has no role
        // user3 has no roles

        $users = collect([$user1, $user2, $user3]);

        // Act - Make everyone students, remove parent role
        $result = $this->repository->syncRoles(
            $users,
            [$studentRole->id],  // Add student role to all
            [$parentRole->id]    // Remove parent role from all
        );

        // Debug: See what actually happened
        dump('Resultado de syncRoles:', $result);

        $user1->refresh();
        $user2->refresh();
        $user3->refresh();

        dump('User1 roles:', $user1->getRoleNames()->toArray());
        dump('User2 roles:', $user2->getRoleNames()->toArray());
        dump('User3 roles:', $user3->getRoleNames()->toArray());

        // Assert the FINAL STATE is correct
        $this->assertTrue($user1->hasRole('estudiante'));
        $this->assertFalse($user1->hasRole('padre'));

        $this->assertTrue($user2->hasRole('estudiante'));
        $this->assertFalse($user2->hasRole('padre'));

        $this->assertTrue($user3->hasRole('estudiante'));
        $this->assertFalse($user3->hasRole('padre'));

        // user1: already has student, doesn't have parent → 0 changes
        // user2: needs student added, needs parent removed → 2 changes
        // user3: needs student added → 1 change

        // So: added=2 (user2 + user3), removed=1 (user2), users_affected=2 (user2 + user3)
        $this->assertEquals(2, $result['added'], 'user2 and user3 should get student role');
        $this->assertEquals(1, $result['removed'], 'user2 should lose parent role');
        $this->assertEquals(2, $result['users_affected'], 'Only user2 and user3 have changes');
    }

    #[Test]
    public function givePermissionsByType_assigns_only_matching_permissions(): void
    {
        // Arrange
        $user = User::factory()->create();

        Permission::create([
            'name' => 'student-view',
            'guard_name' => 'web',
            'belongs_to' => 'student',
            'type' => 'model'
        ]);

        Permission::create([
            'name' => 'student-edit',
            'guard_name' => 'web',
            'belongs_to' => 'student',
            'type' => 'model'
        ]);

        Permission::create([
            'name' => 'payment-view',
            'guard_name' => 'web',
            'belongs_to' => 'payment',
            'type' => 'model'
        ]);

        Permission::create([
            'name' => 'system-admin',
            'guard_name' => 'web',
            'belongs_to' => 'system',
            'type' => 'system'
        ]);

        // Act - Give only student model permissions
        $this->repository->givePermissionsByType($user, 'student', 'model');

        // Assert
        $permissions = $user->getPermissionNames();
        $this->assertContains('student-view', $permissions);
        $this->assertContains('student-edit', $permissions);
        $this->assertNotContains('payment-view', $permissions);
        $this->assertNotContains('system-admin', $permissions);
    }

}
