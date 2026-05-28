<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\DTO\Response\User\UserChangedStatusResponse;
use App\Core\Application\UseCases\Admin\UserManagement\DisableUserUseCase;
use App\Core\Domain\Enum\User\UserStatus;
use App\Exceptions\Conflict\UserAlreadyDisabledException;
use App\Exceptions\Conflict\UserCannotBeDisabledException;
use App\Exceptions\NotFound\UsersNotFoundForUpdateException;
use App\Jobs\ClearStaffCacheJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DisableUserUseCaseTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_disables_active_users_and_returns_response(): void
    {
        // Arrange
        Queue::fake();

        $activeUsers = User::factory()->count(3)->create([
            'status' => UserStatus::ACTIVO
        ]);

        $userIds = $activeUsers->pluck('id')->toArray();

        $useCase = app(DisableUserUseCase::class);

        // Act
        $response = $useCase->execute($userIds);

        // Assert
        $this->assertInstanceOf(UserChangedStatusResponse::class, $response);
        $this->assertEquals(UserStatus::BAJA->value, $response->newStatus);
        $this->assertEquals(3, $response->totalUpdated);

        $updatedUsers = User::whereIn('id', $userIds)->get();
        foreach ($updatedUsers as $user) {
            $this->assertEquals(UserStatus::BAJA, $user->status);
        }

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_disables_temporarily_disabled_users(): void
    {
        // Arrange
        Queue::fake();

        $tempDisabledUsers = User::factory()->count(2)->create([
            'status' => UserStatus::BAJA_TEMPORAL
        ]);

        $userIds = $tempDisabledUsers->pluck('id')->toArray();

        $useCase = app(DisableUserUseCase::class);

        // Act
        $response = $useCase->execute($userIds);

        // Assert
        $this->assertEquals(2, $response->totalUpdated);

        foreach ($tempDisabledUsers as $user) {
            $user->refresh();
            $this->assertEquals(UserStatus::BAJA, $user->status);
        }

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_throws_exception_when_trying_to_disable_already_disabled_users(): void
    {
        // Arrange
        Queue::fake();

        $disabledUsers = User::factory()->count(2)->create([
            'status' => UserStatus::BAJA
        ]);

        $userIds = $disabledUsers->pluck('id')->toArray();

        $useCase = app(DisableUserUseCase::class);

        // Assert
        $this->expectException(UserAlreadyDisabledException::class);

        // Act
        $useCase->execute($userIds);

        Queue::assertNothingPushed();

        foreach ($disabledUsers as $user) {
            $user->refresh();
            $this->assertEquals(UserStatus::BAJA, $user->status);
        }
    }

    #[Test]
    public function it_throws_exception_when_trying_to_disable_deleted_users(): void
    {
        // Arrange
        Queue::fake();

        $deletedUser = User::factory()->create([
            'status' => UserStatus::ELIMINADO
        ]);

        $useCase = app(DisableUserUseCase::class);

        // Assert - Deleted users cannot be disabled (based on your transitions)
        $this->expectException(UserCannotBeDisabledException::class);

        // Act
        $useCase->execute([$deletedUser->id]);

        Queue::assertNothingPushed();

        $deletedUser->refresh();
        $this->assertEquals(UserStatus::ELIMINADO, $deletedUser->status);
    }

    #[Test]
    public function it_handles_large_number_of_users_with_chunking_for_disabling(): void
    {
        // Arrange
        Queue::fake();

        $totalUsers = 750;
        $activeUsers = User::factory()->count($totalUsers)->create([
            'status' => UserStatus::ACTIVO
        ]);

        $userIds = $activeUsers->pluck('id')->toArray();

        $useCase = app(DisableUserUseCase::class);

        // Act
        $response = $useCase->execute($userIds);

        // Assert
        $this->assertEquals($totalUsers, $response->totalUpdated);

        $disabledCount = User::whereIn('id', $userIds)
            ->where('status', UserStatus::BAJA)
            ->count();

        $this->assertEquals($totalUsers, $disabledCount);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_filters_duplicate_ids_before_disabling(): void
    {
        // Arrange
        Queue::fake();

        $user = User::factory()->create([
            'status' => UserStatus::ACTIVO
        ]);

        $duplicateIds = [$user->id, $user->id, $user->id];

        $useCase = app(DisableUserUseCase::class);

        // Act
        $response = $useCase->execute($duplicateIds);

        // Assert
        $this->assertEquals(1, $response->totalUpdated);

        $user->refresh();
        $this->assertEquals(UserStatus::BAJA, $user->status);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_throws_exception_when_no_users_found_for_disabling(): void
    {
        // Arrange
        Queue::fake();

        $nonExistentId = 99999;

        $useCase = app(DisableUserUseCase::class);

        // Assert
        $this->expectException(UsersNotFoundForUpdateException::class);

        // Act
        $useCase->execute([$nonExistentId]);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_throws_exception_when_ids_array_is_empty_for_disabling(): void
    {
        // Arrange
        Queue::fake();

        $useCase = app(DisableUserUseCase::class);

        // Assert
        $this->expectException(UsersNotFoundForUpdateException::class);

        // Act
        $useCase->execute([]);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_handles_mixed_valid_and_invalid_users_for_disabling(): void
    {
        // Arrange
        Queue::fake();

        $activeUser = User::factory()->create(['status' => UserStatus::ACTIVO]);
        $disabledUser = User::factory()->create(['status' => UserStatus::BAJA]);
        $deletedUser = User::factory()->create(['status' => UserStatus::ELIMINADO]);

        $userIds = [$activeUser->id, $disabledUser->id, $deletedUser->id];

        $useCase = app(DisableUserUseCase::class);

        // Assert - Debería fallar porque hay un usuario ya deshabilitado
        $this->expectException(UserAlreadyDisabledException::class);

        // Act
        try {
            $useCase->execute($userIds);
        } catch (UserAlreadyDisabledException $e) {
            // Verificar estado de los usuarios - NINGUNO debería haber cambiado
            $activeUser->refresh();
            $disabledUser->refresh();
            $deletedUser->refresh();

            $this->assertEquals(UserStatus::ACTIVO, $activeUser->status);
            $this->assertEquals(UserStatus::BAJA, $disabledUser->status);
            $this->assertEquals(UserStatus::ELIMINADO, $deletedUser->status);

            throw $e;
        }

        Queue::assertNothingPushed();
    }

    #[Test]
    public function test_concurrent_disable_attempts(): void
    {
        // Arrange
        Queue::fake();

        $user = User::factory()->create([
            'status' => UserStatus::ACTIVO
        ]);

        $useCase = app(DisableUserUseCase::class);

        // Act - Primera deshabilitación exitosa
        $response1 = $useCase->execute([$user->id]);

        // Assert - Primera deshabilitación
        $this->assertEquals(1, $response1->totalUpdated);
        $user->refresh();
        $this->assertEquals(UserStatus::BAJA, $user->status);
        Queue::assertPushed(ClearStaffCacheJob::class);

        // Resetear el fake para contar de nuevo
        Queue::fake();

        // Intentar deshabilitar nuevamente - debería fallar
        $this->expectException(UserAlreadyDisabledException::class);

        try {
            $useCase->execute([$user->id]);
        } catch (UserAlreadyDisabledException $e) {
            $user->refresh();
            $this->assertEquals(UserStatus::BAJA, $user->status);
            Queue::assertNothingPushed();
            throw $e;
        }
    }

}
