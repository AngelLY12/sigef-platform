<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\DTO\Request\User\UpdateUserRoleDTO;
use App\Core\Application\DTO\Response\User\UserWithUpdatedRoleResponse;
use App\Core\Application\UseCases\Admin\RolePermissionManagement\SyncRoleUseCase;
use App\Core\Domain\Enum\User\UserRoles;
use App\Exceptions\NotFound\UsersNotFoundForUpdateException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SyncRoleUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => UserRoles::ADMIN->value, 'guard_name' => 'sanctum']);
        Role::create(['name' => UserRoles::UNVERIFIED->value, 'guard_name' => 'sanctum']);
        Role::create(['name' => UserRoles::STUDENT->value, 'guard_name' => 'sanctum']);
        Role::create(['name' => 'teacher', 'guard_name' => 'sanctum']);
    }

    #[Test]
    public function it_adds_roles_to_users(): void
    {
        $users = User::factory()->count(3)->create();
        $curps = $users->pluck('curp')->toArray();

        $dto = new UpdateUserRoleDTO(
            curps: $curps,
            rolesToAdd: ['teacher'],
            rolesToRemove: []
        );

        $useCase = app(SyncRoleUseCase::class);

        $response = $useCase->execute($dto);

        $this->assertInstanceOf(UserWithUpdatedRoleResponse::class, $response);
        $this->assertEquals(3, $response->summary['totalUpdated']);

        foreach ($users as $user) {
            $this->assertTrue($user->fresh()->hasRole('teacher'));
        }
    }

    #[Test]
    public function it_removes_roles_from_users(): void
    {
        $users = User::factory()->count(2)->create();
        foreach ($users as $user) {
            $user->assignRole('teacher');
        }

        $dto = new UpdateUserRoleDTO(
            curps: $users->pluck('curp')->toArray(),
            rolesToAdd: [],
            rolesToRemove: ['teacher']
        );

        $useCase = app(SyncRoleUseCase::class);
        $response = $useCase->execute($dto);

        $this->assertEquals(2, $response->summary['totalUpdated']);

        foreach ($users as $user) {
            $this->assertFalse($user->fresh()->hasRole('teacher'));
        }
    }

    #[Test]
    public function it_throws_exception_when_users_not_found(): void
    {
        $this->expectException(UsersNotFoundForUpdateException::class);

        $dto = new UpdateUserRoleDTO(
            curps: ['CURP_INEXISTENTE'],
            rolesToAdd: ['teacher'],
            rolesToRemove: []
        );

        app(SyncRoleUseCase::class)->execute($dto);
    }

    #[Test]
    public function it_does_not_update_users_that_already_have_role(): void
    {
        $users = User::factory()->count(2)->create();

        foreach ($users as $user) {
            $user->assignRole(UserRoles::STUDENT->value);
        }

        $dto = new UpdateUserRoleDTO(
            curps: $users->pluck('curp')->toArray(),
            rolesToAdd: [UserRoles::STUDENT->value],
            rolesToRemove: []
        );

        $response = app(SyncRoleUseCase::class)->execute($dto);

        $this->assertEquals(0, $response->summary['totalUpdated']);
    }

    #[Test]
    public function it_update_users_with_unverified_role(): void
    {
        $users = User::factory()->count(2)->create();

        foreach ($users as $user) {
            $user->assignRole(UserRoles::UNVERIFIED->value);

        }

        $dto = new UpdateUserRoleDTO(
            curps: $users->pluck('curp')->toArray(),
            rolesToAdd: [UserRoles::STUDENT->value],
            rolesToRemove: []
        );

        $response = app(SyncRoleUseCase::class)->execute($dto);

        $this->assertEquals(2, $response->summary['totalUpdated']);
        $this->assertEquals(2, $response->summary['operations']['total_roles_removed']);
        $this->assertEquals(2, $response->summary['operations']['total_roles_added']);

        foreach ($users as $user) {
            $user = $user->fresh();
            $this->assertTrue($user->hasRole(UserRoles::STUDENT->value));
            $this->assertFalse($user->hasRole(UserRoles::UNVERIFIED->value));
        }

    }

    #[Test]
    public function it_adds_and_removes_roles_in_one_execution(): void
    {
        $users = User::factory()->count(2)->create();

        foreach ($users as $user) {
            $user->assignRole(UserRoles::UNVERIFIED->value);
        }

        $dto = new UpdateUserRoleDTO(
            curps: $users->pluck('curp')->toArray(),
            rolesToAdd: [UserRoles::STUDENT->value],
            rolesToRemove: [UserRoles::UNVERIFIED->value]
        );

        $response = app(SyncRoleUseCase::class)->execute($dto);

        $this->assertEquals(2, $response->summary['totalUpdated']);

        foreach ($users as $user) {
            $user = $user->fresh();
            $this->assertTrue($user->hasRole(UserRoles::STUDENT->value));
            $this->assertFalse($user->hasRole(UserRoles::UNVERIFIED->value));
        }
    }

    #[Test]
    public function it_handles_large_amount_of_users_when_syncing_roles(): void
    {
        // Arrange
        $users = User::factory()->count(800)->create();
        $curps = $users->pluck('curp')->toArray();

        $dto = new UpdateUserRoleDTO(
            curps: $curps,
            rolesToAdd: ['student'],
            rolesToRemove: []
        );

        // Act
        $response = app(SyncRoleUseCase::class)->execute($dto);

        // Assert
        $this->assertEquals(800, $response->summary['totalFound']);
        $this->assertEquals(800, $response->summary['totalUpdated']);
        $this->assertEquals(0, $response->summary['totalFailed']);
        $this->assertEquals(0, $response->summary['totalUnchanged']);

        // Verificación parcial (no todos, por tiempo)
        $users->take(10)->each(function ($user) {
            $this->assertTrue(
                $user->fresh()->hasRole('student')
            );
        });
    }

}
