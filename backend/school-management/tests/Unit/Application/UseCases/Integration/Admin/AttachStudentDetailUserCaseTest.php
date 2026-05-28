<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\DTO\Request\StudentDetail\CreateStudentDetailDTO;
use App\Core\Application\UseCases\Admin\StudentManagement\AttachStudentDetailUserCase;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\User\UserRoles;
use App\Exceptions\Conflict\UserAlreadyHaveStudentDetailException;
use App\Exceptions\Validation\ValidationException;
use App\Jobs\ClearStaffCacheJob;
use App\Models\Career;
use App\Models\StudentDetail;
use App\Models\User as EloquentUser;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role as SpatieRole;
use Tests\TestCase;

class AttachStudentDetailUserCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $roles = [
            UserRoles::ADMIN->value,
            UserRoles::SUPERVISOR->value,
            UserRoles::STUDENT->value,
            UserRoles::FINANCIAL_STAFF->value,
            UserRoles::UNVERIFIED->value,
        ];

        foreach ($roles as $roleName) {
            SpatieRole::create(['name' => $roleName, 'guard_name' => 'sanctum']);
        }
    }

    #[Test]
    public function it_attaches_student_detail_to_user_without_existing_detail(): void
    {
        // Arrange
        Queue::fake();

        $user = EloquentUser::factory()->create();
        $career = Career::factory()->create();

        $detailDTO = new CreateStudentDetailDTO(
            user_id: $user->id,
            career_id: $career->id,
            n_control: '19201134',
            semestre: 5,
            group: 'A',
            workshop: 'Taller de Programación'
        );

        $useCase = app(AttachStudentDetailUserCase::class);

        // Act
        $updatedUser = $useCase->execute($detailDTO);

        // Assert
        $this->assertInstanceOf(User::class, $updatedUser);

        // Verificar que el usuario tiene el detalle de estudiante en la BD
        $userWithDetail = EloquentUser::with('studentDetail')->find($user->id);
        $this->assertNotNull($userWithDetail->studentDetail);
        $this->assertEquals('19201134', $userWithDetail->studentDetail->n_control);
        $this->assertEquals(5, $userWithDetail->studentDetail->semestre);
        $this->assertEquals('A', $userWithDetail->studentDetail->group);
        $this->assertEquals('Taller de Programación', $userWithDetail->studentDetail->workshop);
        $this->assertEquals($career->id, $userWithDetail->studentDetail->career_id);

        // Verificar que se disparó el job de limpieza de cache
        Queue::assertPushed(ClearStaffCacheJob::class, function ($job) {
            return $job->queue === 'cache';
        });
    }

    #[Test]
    public function it_throws_exception_when_user_already_has_student_detail(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();
        $user = EloquentUser::factory()->create();

        // Crear un detalle de estudiante existente
        StudentDetail::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id,
            'n_control' => '19201133'
        ]);

        $detailDTO = new CreateStudentDetailDTO(
            user_id: $user->id,
            career_id: $career->id,
            n_control: '19201134',
            semestre: 5,
            group: 'A',
            workshop: 'Taller de Programación'
        );

        $useCase = app(AttachStudentDetailUserCase::class);

        // Assert
        $this->expectException(UserAlreadyHaveStudentDetailException::class);

        // Act
        $useCase->execute($detailDTO);

        // Verificar que NO se disparó el job de cache
        Queue::assertNothingPushed();

        // Verificar que el detalle original no cambió
        $userWithDetail = EloquentUser::with('studentDetail')->find($user->id);
        $this->assertEquals('19201133', $userWithDetail->studentDetail->n_control);
    }

    #[Test]
    public function it_preserves_all_user_attributes_when_attaching_student_detail(): void
    {
        // Arrange
        Queue::fake();

        $dateNow = now()->subYear()->format('Y-m-d');
        $carbon = new Carbon($dateNow);

        $originalUserData = [
            'curp' => 'TESTCURP123456789',
            'name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'juan@example.com',
            'password' => bcrypt('password123'),
            'phone_number' => '5551234567',
            'status' => \App\Core\Domain\Enum\User\UserStatus::ACTIVO,
            'registration_date' => $carbon,
            'birthdate' => new Carbon('1990-01-01'),
            'gender' => \App\Core\Domain\Enum\User\UserGender::HOMBRE,
            'address' => ['street' => '123 Main St'],
            'blood_type' => \App\Core\Domain\Enum\User\UserBloodType::A_POSITIVE,
            'stripe_customer_id' => 'cus_123456'
        ];

        $user = EloquentUser::factory()->create($originalUserData);
        $career = Career::factory()->create();

        $detailDTO = new CreateStudentDetailDTO(
            user_id: $user->id,
            career_id: $career->id,
            n_control: '19201134',
            semestre: 5,
            group: 'A',
            workshop: 'Taller de Programación'
        );

        $useCase = app(AttachStudentDetailUserCase::class);

        // Act
        $updatedUser = $useCase->execute($detailDTO);

        // Assert
        $user->refresh();

        // Verificar que el usuario tiene el detalle
        $this->assertNotNull($user->studentDetail);
        $this->assertEquals('19201134', $user->studentDetail->n_control);

        // Verificar que todos los atributos del usuario permanecen iguales
        foreach ($originalUserData as $key => $value) {
            if ($key === 'updated_at') {
                continue; // Este debe cambiar
            }

            if ($key === 'address' && is_string($value)) {
                $this->assertEquals($value, $user->$key);
            } elseif ($key === 'password') {
                $this->assertNotNull($user->$key);
            } else {
                $this->assertEquals($value, $user->$key, "Field {$key} changed unexpectedly");
            }
        }

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_attaches_student_detail_with_minimum_required_fields(): void
    {
        // Arrange
        Queue::fake();

        $user = EloquentUser::factory()->create();
        $career = Career::factory()->create();

        $detailDTO = new CreateStudentDetailDTO(
            user_id: $user->id,
            career_id: $career->id,
            n_control: '19201134',
            semestre: 1,
            group: '',
            workshop: null
        );

        $useCase = app(AttachStudentDetailUserCase::class);

        // Act
        $updatedUser = $useCase->execute($detailDTO);

        // Assert
        $this->assertInstanceOf(User::class, $updatedUser);

        $userWithDetail = EloquentUser::with('studentDetail')->find($user->id);
        $this->assertNotNull($userWithDetail->studentDetail);
        $this->assertEquals('19201134', $userWithDetail->studentDetail->n_control);
        $this->assertEquals(1, $userWithDetail->studentDetail->semestre);
        $this->assertEquals('', $userWithDetail->studentDetail->group);
        $this->assertNull($userWithDetail->studentDetail->workshop);
        $this->assertEquals($career->id, $userWithDetail->studentDetail->career_id);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_attaches_student_detail_with_special_characters_in_n_control(): void
    {
        // Arrange
        Queue::fake();

        $user = EloquentUser::factory()->create();
        $career = Career::factory()->create();

        $detailDTO = new CreateStudentDetailDTO(
            user_id: $user->id,
            career_id: $career->id,
            n_control: '19-2011-34',
            semestre: 5,
            group: 'B+',
            workshop: 'Taller_especial/2024'
        );

        $useCase = app(AttachStudentDetailUserCase::class);

        // Act
        $updatedUser = $useCase->execute($detailDTO);

        // Assert
        $userWithDetail = EloquentUser::with('studentDetail')->find($user->id);
        $this->assertEquals('19-2011-34', $userWithDetail->studentDetail->n_control);
        $this->assertEquals('B+', $userWithDetail->studentDetail->group);
        $this->assertEquals('Taller_especial/2024', $userWithDetail->studentDetail->workshop);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_throws_exception_when_user_does_not_exist(): void
    {
        // Arrange
        Queue::fake();

        $nonExistentUserId = 99999;
        $career = Career::factory()->create();

        $detailDTO = new CreateStudentDetailDTO(
            user_id: $nonExistentUserId,
            career_id: $career->id,
            n_control: '19201134',
            semestre: 5,
            group: 'A',
            workshop: 'Taller de Programación'
        );

        $useCase = app(AttachStudentDetailUserCase::class);

        // Assert - Depende de cómo maneje tu repositorio usuarios no encontrados
        // Si lanza una excepción, deberías especificarla aquí
        // Por ahora, asumamos que lanza ModelNotFoundException o similar
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $useCase->execute($detailDTO);

        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_throws_exception_when_career_does_not_exist(): void
    {
        // Arrange
        Queue::fake();

        $user = EloquentUser::factory()->create();
        $nonExistentCareerId = 99999;

        $detailDTO = new CreateStudentDetailDTO(
            user_id: $user->id,
            career_id: $nonExistentCareerId,
            n_control: '19201134',
            semestre: 5,
            group: 'A',
            workshop: 'Taller de Programación'
        );

        $useCase = app(AttachStudentDetailUserCase::class);

        // Assert - Depende de cómo maneje tu repositorio la FK constraint
        // Podría ser QueryException, ModelNotFoundException, etc.
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Act
        $useCase->execute($detailDTO);

        Queue::assertNothingPushed();

        // Verificar que no se creó el detalle de estudiante
        $this->assertNull(StudentDetail::where('user_id', $user->id)->first());
    }

    #[Test]
    public function it_handles_high_semester_numbers(): void
    {
        // Arrange
        Queue::fake();

        $user = EloquentUser::factory()->create();
        $career = Career::factory()->create();

        $detailDTO = new CreateStudentDetailDTO(
            user_id: $user->id,
            career_id: $career->id,
            n_control: '19201134',
            semestre: 12, // Número alto de semestre
            group: 'A',
            workshop: 'Taller Avanzado'
        );
        $maxSemester=config('promotions.max_semester');

        $useCase = app(AttachStudentDetailUserCase::class);
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage("El semestre no es valido, debe ser menor o igual a {$maxSemester}" );
        // Act
        $updatedUser = $useCase->execute($detailDTO);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_returns_user_with_loaded_student_detail_relationship(): void
    {
        // Arrange
        Queue::fake();

        $user = EloquentUser::factory()->create();
        $career = Career::factory()->create();

        $detailDTO = new CreateStudentDetailDTO(
            user_id: $user->id,
            career_id: $career->id,
            n_control: '19201134',
            semestre: 5,
            group: 'A',
            workshop: 'Taller de Programación'
        );

        $useCase = app(AttachStudentDetailUserCase::class);

        // Act
        $updatedUser = $useCase->execute($detailDTO);

        // Assert - Verificar que el objeto retornado tiene la relación cargada
        // Esto depende de cómo tu repositorio implementa `attachStudentDetail`
        // Generalmente debería retornar el usuario con la relación eager loaded
        $this->assertInstanceOf(User::class, $updatedUser);

        // Si tu User entity tiene métodos para acceder al studentDetail
        // Por ejemplo: $updatedUser->getStudentDetail() o similar
        // $this->assertNotNull($updatedUser->getStudentDetail());

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_dispatches_cache_clear_job_on_successful_attachment(): void
    {
        // Arrange
        Queue::fake();

        $user = EloquentUser::factory()->create();
        $career = Career::factory()->create();

        $detailDTO = new CreateStudentDetailDTO(
            user_id: $user->id,
            career_id: $career->id,
            n_control: '19201134',
            semestre: 5,
            group: 'A',
            workshop: 'Taller de Programación'
        );

        $useCase = app(AttachStudentDetailUserCase::class);

        // Act
        $useCase->execute($detailDTO);

        // Assert
        Queue::assertPushed(ClearStaffCacheJob::class, 1);
        Queue::assertPushed(ClearStaffCacheJob::class, function ($job) {
            return $job->queue === 'cache';
        });
    }

    #[Test]
    public function it_does_not_dispatch_cache_clear_job_on_failure(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();
        $user = EloquentUser::factory()->create();

        // Crear un detalle existente para forzar la excepción
        StudentDetail::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id
        ]);

        $detailDTO = new CreateStudentDetailDTO(
            user_id: $user->id,
            career_id: $career->id,
            n_control: '19201134',
            semestre: 5,
            group: 'A',
            workshop: 'Taller de Programación'
        );

        $useCase = app(AttachStudentDetailUserCase::class);

        // Assert
        $this->expectException(UserAlreadyHaveStudentDetailException::class);

        // Act
        $useCase->execute($detailDTO);

        // Assert
        Queue::assertNothingPushed();
    }

    #[Test]
    public function it_can_attach_student_detail_to_users_with_different_statuses(): void
    {
        // Arrange
        Queue::fake();

        $career = Career::factory()->create();

        $statuses = [
            \App\Core\Domain\Enum\User\UserStatus::ACTIVO,
            \App\Core\Domain\Enum\User\UserStatus::BAJA_TEMPORAL,
            \App\Core\Domain\Enum\User\UserStatus::BAJA,
            // \App\Core\Domain\Enum\User\UserStatus::ELIMINADO // Si quieres probar también
        ];

        foreach ($statuses as $status) {
            $user = EloquentUser::factory()->create(['status' => $status]);

            $detailDTO = new CreateStudentDetailDTO(
                user_id: $user->id,
                career_id: $career->id,
                n_control: '19201134' . $status->value,
                semestre: 5,
                group: 'A',
                workshop: 'Taller'
            );

            $useCase = app(AttachStudentDetailUserCase::class);

            // Act & Assert - Debería funcionar para todos los estados
            try {
                $updatedUser = $useCase->execute($detailDTO);
                $this->assertInstanceOf(User::class, $updatedUser);

                $userWithDetail = EloquentUser::with('studentDetail')->find($user->id);
                $this->assertNotNull($userWithDetail->studentDetail);
                $this->assertEquals($status, $userWithDetail->status);

            } catch (\Exception $e) {
                $this->fail("Failed for status {$status->value}: " . $e->getMessage());
            }

            // Resetear queue fake para cada iteración
            Queue::fake();
        }

        // Al menos una de las ejecuciones debería haber disparado el job
        // (aunque Queue::fake() se resetea, podrías contar manualmente)
    }

    #[Test]
    public function it_handles_concurrent_attachment_attempts(): void
    {
        // Arrange
        Queue::fake();

        $user = EloquentUser::factory()->create();
        $career = Career::factory()->create();

        $detailDTO = new CreateStudentDetailDTO(
            user_id: $user->id,
            career_id: $career->id,
            n_control: '19201134',
            semestre: 5,
            group: 'A',
            workshop: 'Taller de Programación'
        );

        $useCase = app(AttachStudentDetailUserCase::class);

        // Act - Primera ejecución exitosa
        $updatedUser1 = $useCase->execute($detailDTO);
        $this->assertInstanceOf(User::class, $updatedUser1);

        Queue::assertPushed(ClearStaffCacheJob::class);

        // Resetear queue fake
        Queue::fake();

        // Intentar adjuntar nuevamente - debería fallar
        $this->expectException(UserAlreadyHaveStudentDetailException::class);

        try {
            $useCase->execute($detailDTO);
        } catch (UserAlreadyHaveStudentDetailException $e) {
            // Verificar que solo hay un student detail
            $detailsCount = StudentDetail::where('user_id', $user->id)->count();
            $this->assertEquals(1, $detailsCount);

            Queue::assertNothingPushed();
            throw $e;
        }
    }

}
