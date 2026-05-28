<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\DTO\Request\User\UpdateUserPermissionsDTO;
use App\Core\Application\DTO\Response\User\UserWithUpdatedPermissionsResponse;
use App\Core\Application\UseCases\Admin\RolePermissionManagement\SyncPermissionsUseCase;
use App\Exceptions\NotFound\UsersNotFoundForUpdateException;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SyncPermissionsUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionsSeeder::class,
            RolesSeeder::class,
        ]);
    }

    #[Test]
    public function it_adds_model_permissions_to_users_by_role(): void
    {
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $user->assignRole('supervisor');
        }

        $dto = new UpdateUserPermissionsDTO(
            role: 'supervisor',
            permissionsToAdd: ['sync.permissions'],
            permissionsToRemove: []
        );

        $response = app(SyncPermissionsUseCase::class)->execute($dto);

        $this->assertEquals(3, $response->summary['totalUpdated']);

        foreach ($users as $user) {
            $this->assertTrue(
                $user->fresh()->hasPermissionTo('sync.permissions')
            );
        }
    }

    #[Test]
    public function it_removes_model_permissions_from_users(): void
    {
        $users = User::factory()->count(2)->create();

        foreach ($users as $user) {
            $user->assignRole('supervisor');
            $user->givePermissionTo('sync.permissions');
        }

        $dto = new UpdateUserPermissionsDTO(
            role: 'supervisor',
            permissionsToAdd: [],
            permissionsToRemove: ['sync.permissions']
        );

        $response = app(SyncPermissionsUseCase::class)->execute($dto);

        $this->assertEquals(2, $response->summary['totalUpdated']);

        foreach ($users as $user) {
            $this->assertFalse(
                $user->fresh()->hasPermissionTo('sync.permissions')
            );
        }
    }

    #[Test]
    public function it_throws_exception_when_no_users_found(): void
    {
        $this->expectException(UsersNotFoundForUpdateException::class);

        $dto = new UpdateUserPermissionsDTO(
            role: 'supervisor',
            permissionsToAdd: ['sync.permissions'],
            permissionsToRemove: []
        );

        app(SyncPermissionsUseCase::class)->execute($dto);
    }

    #[Test]
    public function it_only_updates_users_that_need_permission_changes(): void
    {
        $users = User::factory()->count(4)->create();

        $users[0]->assignRole('supervisor');
        $users[1]->assignRole('supervisor');
        $users[2]->assignRole('student');
        $users[3]->assignRole('student');

        $dto = new UpdateUserPermissionsDTO(
            role: 'supervisor',
            permissionsToAdd: ['sync.permissions'],
            permissionsToRemove: []
        );

        $response = app(SyncPermissionsUseCase::class)->execute($dto);

        $this->assertEquals(2, $response->summary['totalUpdated']);

        $this->assertTrue($users[0]->fresh()->hasPermissionTo('sync.permissions'));
        $this->assertTrue($users[1]->fresh()->hasPermissionTo('sync.permissions'));
        $this->assertFalse($users[2]->fresh()->hasPermissionTo('sync.permissions'));
        $this->assertFalse($users[3]->fresh()->hasPermissionTo('sync.permissions'));
    }

    #[Test]
    public function it_does_not_update_users_that_already_have_permissions(): void
    {
        $users = User::factory()->count(2)->create();

        foreach ($users as $user) {
            $user->assignRole('supervisor');
            $user->givePermissionTo('sync.permissions');
        }

        $dto = new UpdateUserPermissionsDTO(
            role: 'supervisor',
            permissionsToAdd: ['sync.permissions'],
            permissionsToRemove: []
        );

        $response = app(SyncPermissionsUseCase::class)->execute($dto);

        $this->assertEquals(0, $response->summary['totalUpdated']);
    }

    #[Test]
    public function it_adds_and_removes_permissions_in_one_execution(): void
    {
        $users = User::factory()->count(2)->create();

        foreach ($users as $user) {
            $user->assignRole('supervisor');
            $user->givePermissionTo('view.users');
        }

        $dto = new UpdateUserPermissionsDTO(
            role: 'supervisor',
            permissionsToAdd: ['sync.permissions'],
            permissionsToRemove: ['view.users']
        );

        $response = app(SyncPermissionsUseCase::class)->execute($dto);

        $this->assertEquals(2, $response->summary['totalUpdated']);

        foreach ($users as $user) {
            $this->assertTrue($user->fresh()->hasPermissionTo('sync.permissions'));
            $this->assertFalse($user->fresh()->hasPermissionTo('view.users'));
        }
    }

    #[Test]
    public function it_does_not_apply_permissions_outside_role_context(): void
    {
        $users = User::factory()->count(2)->create();

        foreach ($users as $user) {
            $user->assignRole('student'); // student NO tiene sync.permissions
        }

        $dto = new UpdateUserPermissionsDTO(
            role: 'student',
            permissionsToAdd: ['sync.permissions'],
            permissionsToRemove: []
        );

        $response = app(SyncPermissionsUseCase::class)->execute($dto);

        $this->assertEquals(0, $response->summary['totalUpdated']);
    }

    #[Test]
    public function it_handles_large_amount_of_users_when_syncing_permissions(): void
    {
        // Arrange
        $users = User::factory()->count(800)->create();

        foreach ($users as $user) {
            $user->assignRole('supervisor');
        }

        $dto = new UpdateUserPermissionsDTO(
            role: 'supervisor',
            permissionsToAdd: ['sync.permissions'],
            permissionsToRemove: []
        );

        // Act
        $response = app(SyncPermissionsUseCase::class)->execute($dto);

        // Assert
        $this->assertEquals(800, $response->summary['totalFound']);
        $this->assertEquals(800, $response->summary['totalUpdated']);
        $this->assertEquals(0, $response->summary['totalFailed']);
        $this->assertEquals(0, $response->summary['totalUnchanged']);

        $users->take(10)->each(function ($user) {
            $this->assertTrue(
                $user->fresh()->hasPermissionTo('sync.permissions')
            );
        });
    }

}
