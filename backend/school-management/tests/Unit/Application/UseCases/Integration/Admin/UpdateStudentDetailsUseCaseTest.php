<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\UseCases\Admin\StudentManagement\UpdateStudentDeatilsUseCase;
use App\Core\Domain\Entities\User;
use App\Jobs\ClearStaffCacheJob;
use App\Models\Career;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\User as UserModel;
use App\Models\StudentDetail as StudentDetailModel;

class UpdateStudentDetailsUseCaseTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_student_details_successfully(): void
    {
        // Arrange
        Queue::fake();

        $user = UserModel::factory()->create();
        $career = Career::factory()->create();
        StudentDetailModel::factory()->create(['user_id' => $user->id]);

        $fields = [
            'career_id' => $career->id,
            'n_control' => '20230099',
            'semestre' => 6,
            'group' => 'C',
            'workshop' => 'Taller Avanzado'
        ];

        $useCase = app(UpdateStudentDeatilsUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $fields);

        // Assert
        $this->assertInstanceOf(User::class, $result);

        // Verificar que los campos fueron actualizados en la base de datos
        $studentDetail = StudentDetailModel::where('user_id', $user->id)->first();
        $this->assertEquals($career->id, $studentDetail->career_id);
        $this->assertEquals('20230099', $studentDetail->n_control);
        $this->assertEquals(6, $studentDetail->semestre);
        $this->assertEquals('C', $studentDetail->group);
        $this->assertEquals('Taller Avanzado', $studentDetail->workshop);

        // Verificar que se despachó el job de limpieza de cache
        Queue::assertPushed(ClearStaffCacheJob::class, function ($job) {
            return $job->queue === 'cache';
        });
    }

    #[Test]
    public function it_updates_partial_fields(): void
    {
        // Arrange
        Queue::fake();

        $user = UserModel::factory()->create();
        $career = Career::factory()->create();
        StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id,
            'n_control' => '20230001',
            'semestre' => 5,
            'group' => 'A',
            'workshop' => 'Taller Básico'
        ]);

        // Solo actualizar algunos campos
        $fields = [
            'semestre' => 6,
            'group' => 'B'
        ];

        $useCase = app(UpdateStudentDeatilsUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $fields);

        // Assert
        $this->assertInstanceOf(User::class, $result);

        // Verificar que solo los campos especificados fueron actualizados
        $studentDetail = StudentDetailModel::where('user_id', $user->id)->first();
        $this->assertEquals(6, $studentDetail->semestre);
        $this->assertEquals('B', $studentDetail->group);
        // Campos no especificados deben mantenerse igual
        $this->assertEquals($career->id, $studentDetail->career_id);
        $this->assertEquals('20230001', $studentDetail->n_control);
        $this->assertEquals('Taller Básico', $studentDetail->workshop);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_updates_single_field(): void
    {
        // Arrange
        Queue::fake();

        $user = UserModel::factory()->create();
        StudentDetailModel::factory()->create(['user_id' => $user->id]);

        $fields = [
            'n_control' => '20239999'
        ];

        $useCase = app(UpdateStudentDeatilsUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $fields);

        // Assert
        $this->assertInstanceOf(User::class, $result);

        $studentDetail = StudentDetailModel::where('user_id', $user->id)->first();
        $this->assertEquals('20239999', $studentDetail->n_control);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_handles_empty_fields_array(): void
    {
        // Arrange
        Queue::fake();

        $user = UserModel::factory()->create();
        $studentDetail = StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'n_control' => '20230001',
            'semestre' => 5
        ]);

        $fields = [];

        $useCase = app(UpdateStudentDeatilsUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $fields);

        // Assert
        $this->assertInstanceOf(User::class, $result);

        // Los campos no deben cambiar
        $studentDetail->refresh();
        $this->assertEquals('20230001', $studentDetail->n_control);
        $this->assertEquals(5, $studentDetail->semestre);

        // Aún así debe despachar el job de cache
        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_updates_fields_to_null(): void
    {
        // Arrange
        Queue::fake();

        $user = UserModel::factory()->create();
        StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'n_control' => '20230001',
            'semestre' => 5,
            'group' => 'A'
        ]);

        $fields = [
            'n_control' => null,
            'semestre' => null,
            'group' => null
        ];

        $useCase = app(UpdateStudentDeatilsUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $fields);

        // Assert
        $this->assertInstanceOf(User::class, $result);

        $studentDetail = StudentDetailModel::where('user_id', $user->id)->first();
        $this->assertNull($studentDetail->n_control);
        $this->assertNull($studentDetail->semestre);
        $this->assertNull($studentDetail->group);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_updates_fields_to_empty_strings(): void
    {
        // Arrange
        Queue::fake();

        $user = UserModel::factory()->create();
        StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'n_control' => '20230001',
            'group' => 'A'
        ]);

        $fields = [
            'n_control' => '',
            'group' => ''
        ];

        $useCase = app(UpdateStudentDeatilsUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $fields);

        // Assert
        $this->assertInstanceOf(User::class, $result);

        $studentDetail = StudentDetailModel::where('user_id', $user->id)->first();
        $this->assertEquals('', $studentDetail->n_control);
        $this->assertEquals('', $studentDetail->group);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_returns_updated_user_entity(): void
    {
        // Arrange
        Queue::fake();

        $user = UserModel::factory()->create();
        StudentDetailModel::factory()->create(['user_id' => $user->id]);

        $fields = ['semestre' => 7];

        $useCase = app(UpdateStudentDeatilsUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $fields);

        // Assert - Verificar propiedades básicas del User entity
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
        $this->assertEquals($user->email, $result->email);
        // Puedes agregar más assertions según las propiedades de tu User entity

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_dispatches_cache_clear_job_on_queue(): void
    {
        // Arrange
        Queue::fake();

        $user = UserModel::factory()->create();
        StudentDetailModel::factory()->create(['user_id' => $user->id]);

        $fields = ['semestre' => 8];
        $useCase = app(UpdateStudentDeatilsUseCase::class);

        // Act
        $useCase->execute($user->id, $fields);

        // Assert - Verificar que el job fue despachado en la cola correcta
        Queue::assertPushedOn('cache', ClearStaffCacheJob::class);
        Queue::assertPushed(ClearStaffCacheJob::class, 1); // Exactamente una vez
    }

    #[Test]
    public function it_handles_special_characters_in_fields(): void
    {
        // Arrange
        Queue::fake();

        $user = UserModel::factory()->create();
        StudentDetailModel::factory()->create(['user_id' => $user->id]);

        $fields = [
            'n_control' => '2023-001-ABC',
            'group' => 'Grupo-A',
            'workshop' => 'Taller de Ingeniería (Avanzado)'
        ];

        $useCase = app(UpdateStudentDeatilsUseCase::class);

        // Act
        $result = $useCase->execute($user->id, $fields);

        // Assert
        $this->assertInstanceOf(User::class, $result);

        $studentDetail = StudentDetailModel::where('user_id', $user->id)->first();
        $this->assertEquals('2023-001-ABC', $studentDetail->n_control);
        $this->assertEquals('Grupo-A', $studentDetail->group);
        $this->assertEquals('Taller de Ingeniería (Avanzado)', $studentDetail->workshop);

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

    #[Test]
    public function it_updates_multiple_students_independently(): void
    {
        // Arrange
        Queue::fake();

        $user1 = UserModel::factory()->create();
        $user2 = UserModel::factory()->create();

        StudentDetailModel::factory()->create(['user_id' => $user1->id, 'n_control' => '001']);
        StudentDetailModel::factory()->create(['user_id' => $user2->id, 'n_control' => '002']);

        $useCase = app(UpdateStudentDeatilsUseCase::class);

        // Act - Actualizar solo el primer usuario
        $fields1 = ['n_control' => '001-updated'];
        $result1 = $useCase->execute($user1->id, $fields1);

        // Assert - Solo el primer usuario debe actualizarse
        $studentDetail1 = StudentDetailModel::where('user_id', $user1->id)->first();
        $studentDetail2 = StudentDetailModel::where('user_id', $user2->id)->first();

        $this->assertEquals('001-updated', $studentDetail1->n_control);
        $this->assertEquals('002', $studentDetail2->n_control); // Sin cambios

        Queue::assertPushed(ClearStaffCacheJob::class);
    }

}
