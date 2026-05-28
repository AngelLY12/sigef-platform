<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\DTO\Response\User\UserChangedStatusResponse;
use App\Core\Application\UseCases\Admin\UserManagement\DeleteLogicalUserUseCase;
use App\Core\Domain\Enum\User\UserStatus;
use App\Exceptions\Conflict\UserAlreadyDeletedException;
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

class DeleteLogicalUserUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_logically_deletes_active_users_and_returns_response(): void
    {
        // Arrange
        Queue::fake();

        $activeUsers = User::factory()->count(3)->create([
            'status' => UserStatus::ACTIVO
        ]);

        $userIds = $activeUsers->pluck('id')->toArray();

        $useCase = app(DeleteLogicalUserUseCase::class);

        // Act
        $response = $useCase->execute($userIds);

        // Assert
        $this->assertInstanceOf(UserChangedStatusResponse::class, $response);
        $this->assertEquals(UserStatus::ELIMINADO->value, $response->newStatus);
        $this->assertEquals(3, $response->totalUpdated);

        // Verificar que los usuarios realmente cambiaron de estado en la BD
        $updatedUsers = User::whereIn('id', $userIds)->get();
        foreach ($updatedUsers as $user) {
            $this->assertEquals(UserStatus::ELIMINADO, $user->status);
        }

        // Verificar que se disparó el job de limpieza de cache
        Queue::assertPushed(ClearStaffCacheJob::class, function ($job) {
            return $job->queue === 'cache';
        });
    }

    #[Test]
    public function it_logically_deletes_temporarily_disabled_users(): void
    {
        // Arrange
        Queue::fake();

        $tempDisabledUsers = User::factory()->count(2)->create([
            'status' => UserStatus::BAJA_TEMPORAL
        ]);

        $userIds = $tempDisabledUsers->pluck('id')->toArray();

        $useCase = app(DeleteLogicalUserUseCase::class);

        // Act
        $response = $useCase->execute($userIds);

        // Assert
        $this->assertEquals(2, $response->totalUpdated);

        foreach ($tempDisabledUsers as $user) {
            $user->refresh();
            $this->assertEquals(UserStatus::ELIMINADO, $user->status);
        }

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_logically_deletes_permanently_disabled_users(): void
    {
        // Arrange
        Queue::fake();

        $disabledUsers = User::factory()->count(2)->create([
            'status' => UserStatus::BAJA
        ]);

        $userIds = $disabledUsers->pluck('id')->toArray();

        $useCase = app(DeleteLogicalUserUseCase::class);

        // Act
        $response = $useCase->execute($userIds);

        // Assert
        $this->assertEquals(2, $response->totalUpdated);

        foreach ($disabledUsers as $user) {
            $user->refresh();
            $this->assertEquals(UserStatus::ELIMINADO, $user->status);
        }

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_throws_exception_when_trying_to_delete_already_deleted_users(): void
    {
        // Arrange
        Queue::fake();

        $deletedUsers = User::factory()->count(2)->create([
            'status' => UserStatus::ELIMINADO
        ]);

        $userIds = $deletedUsers->pluck('id')->toArray();

        $useCase = app(DeleteLogicalUserUseCase::class);

        // Assert
        $this->expectException(UserAlreadyDeletedException::class);

        // Act
        $useCase->execute($userIds);

        // Verificar que NO se disparó el job de cache
        Queue::assertNothingPushed();

        // Verificar que los usuarios siguen eliminados
        foreach ($deletedUsers as $user) {
            $user->refresh();
            $this->assertEquals(UserStatus::ELIMINADO, $user->status);
        }
    }

    #[Test]
    public function it_handles_large_number_of_users_with_chunking_for_deletion(): void
    {
        // Arrange
        Queue::fake();

        $totalUsers = 750;
        $activeUsers = User::factory()->count($totalUsers)->create([
            'status' => UserStatus::ACTIVO
        ]);

        $userIds = $activeUsers->pluck('id')->toArray();

        $useCase = app(DeleteLogicalUserUseCase::class);

        // Act
        $response = $useCase->execute($userIds);

        // Assert
        $this->assertEquals($totalUsers, $response->totalUpdated);

        $deletedCount = User::whereIn('id', $userIds)
            ->where('status', UserStatus::ELIMINADO)
            ->count();

        $this->assertEquals($totalUsers, $deletedCount);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_filters_duplicate_ids_before_logical_deletion(): void
    {
        // Arrange
        Queue::fake();

        $user = User::factory()->create([
            'status' => UserStatus::ACTIVO
        ]);

        $duplicateIds = [$user->id, $user->id, $user->id];

        $useCase = app(DeleteLogicalUserUseCase::class);

        // Act
        $response = $useCase->execute($duplicateIds);

        // Assert
        $this->assertEquals(1, $response->totalUpdated);

        $user->refresh();
        $this->assertEquals(UserStatus::ELIMINADO, $user->status);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_throws_exception_when_no_users_found_for_deletion(): void
    {
        // Arrange
        Queue::fake();

        $nonExistentId = 99999;

        $useCase = app(DeleteLogicalUserUseCase::class);

        // Assert
        $this->expectException(UsersNotFoundForUpdateException::class);

        // Act
        $useCase->execute([$nonExistentId]);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_throws_exception_when_ids_array_is_empty_for_deletion(): void
    {
        // Arrange
        Queue::fake();

        $useCase = app(DeleteLogicalUserUseCase::class);

        // Assert
        $this->expectException(UsersNotFoundForUpdateException::class);

        // Act
        $useCase->execute([]);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_handles_mixed_valid_and_invalid_users_for_deletion(): void
    {
        // Arrange
        Queue::fake();

        $activeUser = User::factory()->create(['status' => UserStatus::ACTIVO]);
        $deletedUser = User::factory()->create(['status' => UserStatus::ELIMINADO]);
        $tempDisabledUser = User::factory()->create(['status' => UserStatus::BAJA_TEMPORAL]);

        $userIds = [$activeUser->id, $deletedUser->id, $tempDisabledUser->id];

        $useCase = app(DeleteLogicalUserUseCase::class);

        // Assert - Debería fallar porque hay un usuario ya eliminado
        $this->expectException(UserAlreadyDeletedException::class);

        // Act
        try {
            $useCase->execute($userIds);
        } catch (UserAlreadyDeletedException $e) {
            // Verificar estado de los usuarios - NINGUNO debería haber cambiado
            $activeUser->refresh();
            $deletedUser->refresh();
            $tempDisabledUser->refresh();

            $this->assertEquals(UserStatus::ACTIVO, $activeUser->status);
            $this->assertEquals(UserStatus::ELIMINADO, $deletedUser->status);
            $this->assertEquals(UserStatus::BAJA_TEMPORAL, $tempDisabledUser->status);

            throw $e;
        }

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_logically_deletes_users_with_related_data_correctly(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();

        $studentUser = User::factory()->create([
            'status' => UserStatus::ACTIVO,
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

        $useCase = app(DeleteLogicalUserUseCase::class);

        // Act
        $response = $useCase->execute([$studentUser->id]);

        // Assert
        $this->assertEquals(1, $response->totalUpdated);

        $user = User::with('studentDetail')->find($studentUser->id);
        $this->assertEquals(UserStatus::ELIMINADO, $user->status);

        // Verificar que los datos relacionados no cambiaron
        $this->assertEquals('Juan Pérez', $user->name);
        $this->assertEquals('juan@example.com', $user->email);
        $this->assertEquals('CURP1234567890123', $user->curp);
        $this->assertEquals('19201134', $user->studentDetail->n_control);
        $this->assertEquals(5, $user->studentDetail->semestre);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_preserves_all_original_attributes_except_status_when_logically_deleting(): void
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

        $useCase = app(DeleteLogicalUserUseCase::class);

        // Act
        $response = $useCase->execute([$user->id]);

        // Assert
        $this->assertEquals(1, $response->totalUpdated);

        $user->refresh();

        // Verificar que el status cambió
        $this->assertEquals(UserStatus::ELIMINADO, $user->status);
        $this->assertNotEmpty($user->mark_as_deleted_at);
        dump("deleted at: {$user->mark_as_deleted_at}");

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

    #[Test]
    public function it_handles_database_transactions_correctly_on_failure_for_deletion(): void
    {
        // Arrange
        Queue::fake();

        $activeUser = User::factory()->create(['status' => UserStatus::ACTIVO]);
        $deletedUser = User::factory()->create(['status' => UserStatus::ELIMINADO]);

        $userIds = [$activeUser->id, $deletedUser->id];

        $useCase = app(DeleteLogicalUserUseCase::class);

        // Assert
        $this->expectException(UserAlreadyDeletedException::class);

        // Act
        try {
            $useCase->execute($userIds);
        } catch (UserAlreadyDeletedException $e) {
            // Verificar que NINGUN usuario cambió (rollback)
            $activeUser->refresh();
            $deletedUser->refresh();

            $this->assertEquals(UserStatus::ACTIVO, $activeUser->status);
            $this->assertEquals(UserStatus::ELIMINADO, $deletedUser->status);

            Queue::assertNothingPushed();

            throw $e;
        }
    }

}
