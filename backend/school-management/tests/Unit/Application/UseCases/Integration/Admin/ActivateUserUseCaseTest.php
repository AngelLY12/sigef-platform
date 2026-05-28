<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\DTO\Response\User\UserChangedStatusResponse;
use App\Core\Application\UseCases\Admin\UserManagement\ActivateUserUseCase;
use App\Core\Domain\Enum\User\UserStatus;
use App\Exceptions\Conflict\UserAlreadyActiveException;
use App\Exceptions\Conflict\UserConflictStatusException;
use App\Exceptions\NotFound\UsersNotFoundForUpdateException;
use App\Jobs\ClearStaffCacheJob;
use App\Models\Career;
use App\Models\StudentDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ActivateUserUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

    }
    #[Test]
    public function it_activates_inactive_users_and_returns_response(): void
    {
        // Arrange
        Queue::fake();

        $inactiveUsers = User::factory()->count(3)->create([
            'status' => UserStatus::BAJA
        ]);

        $userIds = $inactiveUsers->pluck('id')->toArray();

        $useCase = app(ActivateUserUseCase::class);

        // Act
        $response = $useCase->execute($userIds);

        // Assert
        $this->assertInstanceOf(UserChangedStatusResponse::class, $response);
        $this->assertEquals(UserStatus::ACTIVO->value, $response->newStatus);
        $this->assertEquals(3, $response->totalUpdated);

        // Verificar que los usuarios realmente cambiaron de estado en la BD
        $updatedUsers = User::whereIn('id', $userIds)->get();
        foreach ($updatedUsers as $user) {
            $this->assertEquals(UserStatus::ACTIVO, $user->status);
        }

        // Verificar que se disparó el job de limpieza de cache
        Queue::assertPushed(ClearStaffCacheJob::class, function ($job) {
            return $job->queue === 'cache';
        });
    }

    #[Test]
    public function it_throws_exception_when_trying_to_activate_already_active_users(): void
    {
        // Arrange
        Queue::fake();

        $activeUsers = User::factory()->count(2)->create([
            'status' => UserStatus::ACTIVO
        ]);

        $userIds = $activeUsers->pluck('id')->toArray();

        $useCase = app(ActivateUserUseCase::class);

        // Assert
        $this->expectException(UserAlreadyActiveException::class);
        // Act
        $useCase->execute($userIds);

        // Verificar que NO se disparó el job de cache (porque falló la validación)
        Queue::assertNothingPushed();

        // Verificar que los usuarios siguen activos
        foreach ($activeUsers as $user) {
            $user->refresh();
            $this->assertEquals(UserStatus::ACTIVO, $user->status);
        }
    }

    #[Test]
    public function it_activates_suspended_users_successfully(): void
    {
        // Arrange
        Queue::fake();

        $suspendedUsers = User::factory()->count(2)->create([
            'status' => UserStatus::BAJA_TEMPORAL
        ]);

        $userIds = $suspendedUsers->pluck('id')->toArray();

        $useCase = app(ActivateUserUseCase::class);

        // Act
        $response = $useCase->execute($userIds);

        // Assert
        $this->assertEquals(2, $response->totalUpdated);

        foreach ($suspendedUsers as $user) {
            $user->refresh();
            $this->assertEquals(UserStatus::ACTIVO, $user->status);
        }

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_handles_large_number_of_users_with_chunking(): void
    {
        // Arrange
        Queue::fake();

        // Crear más usuarios que el CHUNK_SIZE (500)
        $totalUsers = 750;
        $inactiveUsers = User::factory()->count($totalUsers)->create([
            'status' => UserStatus::BAJA
        ]);

        $userIds = $inactiveUsers->pluck('id')->toArray();

        $useCase = app(ActivateUserUseCase::class);

        // Act
        $response = $useCase->execute($userIds);

        // Assert
        $this->assertEquals($totalUsers, $response->totalUpdated);

        // Verificar que todos fueron activados
        $activatedCount = User::whereIn('id', $userIds)
            ->where('status', UserStatus::ACTIVO)
            ->count();

        $this->assertEquals($totalUsers, $activatedCount);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_filters_duplicate_ids_before_processing(): void
    {
        // Arrange
        Queue::fake();

        $user = User::factory()->create([
            'status' => UserStatus::BAJA
        ]);

        // IDs duplicados
        $duplicateIds = [$user->id, $user->id, $user->id];

        $useCase = app(ActivateUserUseCase::class);

        // Act
        $response = $useCase->execute($duplicateIds);

        // Assert
        // Debería activar solo una vez
        $this->assertEquals(1, $response->totalUpdated);

        $user->refresh();
        $this->assertEquals(UserStatus::ACTIVO, $user->status);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_throws_exception_when_no_users_found(): void
    {
        // Arrange
        Queue::fake();

        // ID que no existe
        $nonExistentId = 99999;

        $useCase = app(ActivateUserUseCase::class);

        // Assert
        $this->expectException(UsersNotFoundForUpdateException::class);

        // Act
        $useCase->execute([$nonExistentId]);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_throws_exception_when_ids_array_is_empty(): void
    {
        // Arrange
        Queue::fake();

        $useCase = app(ActivateUserUseCase::class);

        // Assert
        $this->expectException(UsersNotFoundForUpdateException::class);

        // Act
        $useCase->execute([]);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_handles_mixed_valid_and_invalid_users(): void
    {
        // Arrange
        Queue::fake();

        $inactiveUser = User::factory()->create(['status' => UserStatus::BAJA]);
        $activeUser = User::factory()->create(['status' => UserStatus::ACTIVO]);
        $suspendedUser = User::factory()->create(['status' => UserStatus::BAJA]);

        $userIds = [$inactiveUser->id, $activeUser->id, $suspendedUser->id];

        $useCase = app(ActivateUserUseCase::class);

        // Assert - Debería fallar porque hay un usuario activo
        $this->expectException(UserAlreadyActiveException::class);

        // Act
        try {
            $useCase->execute($userIds);
        } catch (UserConflictStatusException $e) {
            // Verificar estado de los usuarios
            $inactiveUser->refresh();
            $activeUser->refresh();
            $suspendedUser->refresh();

            // NINGUNO debería haber cambiado porque la validación falló antes
            $this->assertEquals(UserStatus::BAJA, $inactiveUser->status);
            $this->assertEquals(UserStatus::ACTIVO, $activeUser->status);
            $this->assertEquals(UserStatus::BAJA, $suspendedUser->status);

            throw $e;
        }

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_activates_users_with_related_data_correctly(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();

        $studentUser = User::factory()->create([
            'status' => UserStatus::BAJA,
            'name' => 'Juan Pérez',
            'email' => 'juan@example.com',
            'curp' => 'CURP1234567890123'
        ]);

        StudentDetail::factory()->create([
            'user_id' => $studentUser->id,
            'career_id' => $career->id,
            'n_control' => '19201134',
            'semestre' => 5
        ]);

        $useCase = app(ActivateUserUseCase::class);

        // Act
        $response = $useCase->execute([$studentUser->id]);

        // Assert
        $this->assertEquals(1, $response->totalUpdated);

        $user = User::with('studentDetail')->find($studentUser->id);
        $this->assertEquals(UserStatus::ACTIVO, $user->status);

        // Verificar que los datos relacionados no cambiaron
        $this->assertEquals('Juan Pérez', $user->name);
        $this->assertEquals('juan@example.com', $user->email);
        $this->assertEquals('CURP1234567890123', $user->curp);
        $this->assertEquals('19201134', $user->studentDetail->n_control);
        $this->assertEquals(5, $user->studentDetail->semestre);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_preserves_all_original_attributes_except_status(): void
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
            'status' => UserStatus::BAJA,
            'registration_date' => $carbon,
            'birthdate' => new Carbon('1990-01-01'),
            'gender' => \App\Core\Domain\Enum\User\UserGender::HOMBRE,
            'address' => ['street' => '123 Main St'],
            'blood_type' => \App\Core\Domain\Enum\User\UserBloodType::A_POSITIVE,
            'stripe_customer_id' => 'cus_123456'
        ];

        $user = User::factory()->create($originalData);

        $useCase = app(ActivateUserUseCase::class);

        // Act
        $response = $useCase->execute([$user->id]);

        // Assert
        $this->assertEquals(1, $response->totalUpdated);

        $user->refresh();

        // Verificar que el status cambió
        $this->assertEquals(UserStatus::ACTIVO, $user->status);

        // Verificar que todos los demás campos permanecen iguales
        foreach ($originalData as $key => $value) {
            if ($key === 'status' || $key === 'updated_at') {
                continue; // Estos deben cambiar
            }

            if ($key === 'address' && is_string($value)) {
                $this->assertEquals($value, $user->$key);
            } elseif ($key === 'password') {
                // Para password, solo verificar que no sea null
                $this->assertNotNull($user->$key);
            } else {
                $this->assertEquals($value, $user->$key, "Field {$key} changed unexpectedly");
            }
        }
    }

    #[Test]
    public function test_concurrent_activation_attempts(): void
    {
        // Arrange
        Queue::fake();

        $user = User::factory()->create([
            'status' => UserStatus::BAJA
        ]);

        $useCase = app(ActivateUserUseCase::class);

        // Act - Primera activación exitosa
        $response1 = $useCase->execute([$user->id]);

        // Assert - Primera activación
        $this->assertEquals(1, $response1->totalUpdated);
        $user->refresh();
        $this->assertEquals(UserStatus::ACTIVO, $user->status);
        Queue::assertPushed(ClearStaffCacheJob::class);

        // Resetear el fake para contar de nuevo
        Queue::fake();

        // Intentar activar nuevamente - debería fallar
        $this->expectException(UserAlreadyActiveException::class);

        try {
            $useCase->execute([$user->id]);
        } catch (UserConflictStatusException $e) {
            // Verificar que sigue activo
            $user->refresh();
            $this->assertEquals(UserStatus::ACTIVO, $user->status);

            // Verificar que NO se disparó el job esta vez
            Queue::assertNothingPushed();

            throw $e;
        }
    }

    #[Test]
    public function it_handles_database_transactions_correctly_on_failure(): void
    {
        // Arrange
        Queue::fake();

        // Crear un usuario que será válido y uno que causará error
        $validUser = User::factory()->create(['status' => UserStatus::BAJA]);
        $activeUser = User::factory()->create(['status' => UserStatus::ACTIVO]);

        $userIds = [$validUser->id, $activeUser->id];

        $useCase = app(ActivateUserUseCase::class);

        // Assert
        $this->expectException(UserAlreadyActiveException::class);

        // Act
        try {
            $useCase->execute($userIds);
        } catch (UserConflictStatusException $e) {
            // Verificar que NINGUN usuario cambió (rollback)
            $validUser->refresh();
            $activeUser->refresh();

            $this->assertEquals(UserStatus::BAJA, $validUser->status);
            $this->assertEquals(UserStatus::ACTIVO, $activeUser->status);

            Queue::assertNothingPushed();

            throw $e;
        }
    }

}
