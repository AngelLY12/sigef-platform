<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\DTO\Response\User\UserChangedStatusResponse;
use App\Core\Application\UseCases\Admin\UserManagement\TemporaryDisableUserUseCase;
use App\Core\Domain\Enum\User\UserStatus;
use App\Exceptions\Conflict\UserAlreadyDisabledException;
use App\Exceptions\Conflict\UserConflictStatusException;
use App\Exceptions\NotFound\UsersNotFoundForUpdateException;
use App\Jobs\ClearStaffCacheJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TemporaryDisableUserUseCaseTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_temporarily_disables_active_users_and_returns_response(): void
    {
        // Arrange
        Queue::fake();

        $activeUsers = User::factory()->count(3)->create([
            'status' => UserStatus::ACTIVO
        ]);

        $userIds = $activeUsers->pluck('id')->toArray();

        $useCase = app(TemporaryDisableUserUseCase::class);

        // Act
        $response = $useCase->execute($userIds);

        // Assert
        $this->assertInstanceOf(UserChangedStatusResponse::class, $response);
        $this->assertEquals(UserStatus::BAJA_TEMPORAL->value, $response->newStatus);
        $this->assertEquals(3, $response->totalUpdated);

        $updatedUsers = User::whereIn('id', $userIds)->get();
        foreach ($updatedUsers as $user) {
            $this->assertEquals(UserStatus::BAJA_TEMPORAL, $user->status);
        }

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_throws_exception_when_trying_to_temporarily_disable_already_temporarily_disabled_users(): void
    {
        // Arrange
        Queue::fake();

        $tempDisabledUsers = User::factory()->count(2)->create([
            'status' => UserStatus::BAJA_TEMPORAL
        ]);

        $userIds = $tempDisabledUsers->pluck('id')->toArray();

        $useCase = app(TemporaryDisableUserUseCase::class);

        // Assert
        $this->expectException(UserAlreadyDisabledException::class);

        // Act
        $useCase->execute($userIds);

        Queue::assertNothingPushed();

        foreach ($tempDisabledUsers as $user) {
            $user->refresh();
            $this->assertEquals(UserStatus::BAJA_TEMPORAL, $user->status);
        }
    }

    #[Test]
    public function it_throws_exception_when_trying_to_temporarily_disable_permanently_disabled_users(): void
    {
        // Arrange
        Queue::fake();

        $disabledUsers = User::factory()->count(2)->create([
            'status' => UserStatus::BAJA
        ]);

        $userIds = $disabledUsers->pluck('id')->toArray();

        $useCase = app(TemporaryDisableUserUseCase::class);

        // Assert - Según tus transiciones, BAJA no puede ir a BAJA_TEMPORAL
        $this->expectException(UserConflictStatusException::class);

        // Act
        $useCase->execute($userIds);

        Queue::assertNothingPushed();

        foreach ($disabledUsers as $user) {
            $user->refresh();
            $this->assertEquals(UserStatus::BAJA, $user->status);
        }
    }

    #[Test]
    public function it_throws_exception_when_trying_to_temporarily_disable_deleted_users(): void
    {
        // Arrange
        Queue::fake();

        $deletedUser = User::factory()->create([
            'status' => UserStatus::ELIMINADO
        ]);

        $useCase = app(TemporaryDisableUserUseCase::class);

        // Assert - Deleted users cannot be temporarily disabled
        $this->expectException(UserConflictStatusException::class);

        // Act
        $useCase->execute([$deletedUser->id]);

        Queue::assertNothingPushed();

        $deletedUser->refresh();
        $this->assertEquals(UserStatus::ELIMINADO, $deletedUser->status);
    }

    #[Test]
    public function it_handles_large_number_of_users_with_chunking_for_temporary_disabling(): void
    {
        // Arrange
        Queue::fake();

        $totalUsers = 750;
        $activeUsers = User::factory()->count($totalUsers)->create([
            'status' => UserStatus::ACTIVO
        ]);

        $userIds = $activeUsers->pluck('id')->toArray();

        $useCase = app(TemporaryDisableUserUseCase::class);

        // Act
        $response = $useCase->execute($userIds);

        // Assert
        $this->assertEquals($totalUsers, $response->totalUpdated);

        $tempDisabledCount = User::whereIn('id', $userIds)
            ->where('status', UserStatus::BAJA_TEMPORAL)
            ->count();

        $this->assertEquals($totalUsers, $tempDisabledCount);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_filters_duplicate_ids_before_temporary_disabling(): void
    {
        // Arrange
        Queue::fake();

        $user = User::factory()->create([
            'status' => UserStatus::ACTIVO
        ]);

        $duplicateIds = [$user->id, $user->id, $user->id];

        $useCase = app(TemporaryDisableUserUseCase::class);

        // Act
        $response = $useCase->execute($duplicateIds);

        // Assert
        $this->assertEquals(1, $response->totalUpdated);

        $user->refresh();
        $this->assertEquals(UserStatus::BAJA_TEMPORAL, $user->status);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_throws_exception_when_no_users_found_for_temporary_disabling(): void
    {
        // Arrange
        Queue::fake();

        $nonExistentId = 99999;

        $useCase = app(TemporaryDisableUserUseCase::class);

        // Assert
        $this->expectException(UsersNotFoundForUpdateException::class);

        // Act
        $useCase->execute([$nonExistentId]);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_throws_exception_when_ids_array_is_empty_for_temporary_disabling(): void
    {
        // Arrange
        Queue::fake();

        $useCase = app(TemporaryDisableUserUseCase::class);

        // Assert
        $this->expectException(UsersNotFoundForUpdateException::class);

        // Act
        $useCase->execute([]);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_handles_mixed_valid_and_invalid_users_for_temporary_disabling(): void
    {
        // Arrange
        Queue::fake();

        $activeUser = User::factory()->create(['status' => UserStatus::ACTIVO]);
        $tempDisabledUser = User::factory()->create(['status' => UserStatus::BAJA_TEMPORAL]);
        $permanentlyDisabledUser = User::factory()->create(['status' => UserStatus::BAJA]);

        $userIds = [$activeUser->id, $tempDisabledUser->id, $permanentlyDisabledUser->id];

        $useCase = app(TemporaryDisableUserUseCase::class);

        // Assert - Debería fallar porque hay un usuario ya deshabilitado temporalmente
        $this->expectException(UserAlreadyDisabledException::class);

        // Act
        try {
            $useCase->execute($userIds);
        } catch (UserAlreadyDisabledException $e) {
            // Verificar estado de los usuarios - NINGUNO debería haber cambiado
            $activeUser->refresh();
            $tempDisabledUser->refresh();
            $permanentlyDisabledUser->refresh();

            $this->assertEquals(UserStatus::ACTIVO, $activeUser->status);
            $this->assertEquals(UserStatus::BAJA_TEMPORAL, $tempDisabledUser->status);
            $this->assertEquals(UserStatus::BAJA, $permanentlyDisabledUser->status);

            throw $e;
        }

        Queue::assertNothingPushed();
    }

    #[Test]
    public function test_concurrent_temporary_disable_attempts(): void
    {
        // Arrange
        Queue::fake();

        $user = User::factory()->create([
            'status' => UserStatus::ACTIVO
        ]);

        $useCase = app(TemporaryDisableUserUseCase::class);

        // Act - Primera deshabilitación temporal exitosa
        $response1 = $useCase->execute([$user->id]);

        // Assert - Primera deshabilitación
        $this->assertEquals(1, $response1->totalUpdated);
        $user->refresh();
        $this->assertEquals(UserStatus::BAJA_TEMPORAL, $user->status);
        Queue::assertPushed(ClearStaffCacheJob::class);

        // Resetear el fake para contar de nuevo
        Queue::fake();

        // Intentar deshabilitar temporalmente nuevamente - debería fallar
        $this->expectException(UserAlreadyDisabledException::class);

        try {
            $useCase->execute([$user->id]);
        } catch (UserAlreadyDisabledException $e) {
            $user->refresh();
            $this->assertEquals(UserStatus::BAJA_TEMPORAL, $user->status);
            Queue::assertNothingPushed();
            throw $e;
        }
    }

    #[Test]
    public function it_preserves_all_original_attributes_except_status_when_temporarily_disabling(): void
    {
        // Arrange
        Queue::fake();
        $dateNow = now()->subYear()->format('Y-m-d');
        $carbon = new Carbon($dateNow);

        $originalData = [
            'curp' => 'TESTCURP123456789',
            'name' => 'Test User',
            'last_name' => 'Last Name',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'phone_number' => '5551234567',
            'status' => UserStatus::ACTIVO,
            'registration_date' => $carbon,
            'birthdate' => new Carbon('1990-01-01'),
            'gender' => \App\Core\Domain\Enum\User\UserGender::HOMBRE,
            'address' => ['street' => '123 Main St'],
            'blood_type' => \App\Core\Domain\Enum\User\UserBloodType::A_POSITIVE,
            'stripe_customer_id' => 'cus_123456'
        ];

        $user = User::factory()->create($originalData);

        $useCase = app(TemporaryDisableUserUseCase::class);

        // Act
        $response = $useCase->execute([$user->id]);

        // Assert
        $this->assertEquals(1, $response->totalUpdated);

        $user->refresh();

        // Verificar que el status cambió
        $this->assertEquals(UserStatus::BAJA_TEMPORAL, $user->status);

        // Verificar que todos los demás campos permanecen iguales
        foreach ($originalData as $key => $value) {
            if ($key === 'status' || $key === 'updated_at') {
                continue;
            }

            if ($key === 'address' && is_string($value)) {
                $this->assertEquals($value, $user->$key);
            } elseif ($key === 'password') {
                $this->assertNotNull($user->$key);
            } else {
                $this->assertEquals($value, $user->$key, "Field {$key} changed unexpectedly");
            }
        }
    }

}
