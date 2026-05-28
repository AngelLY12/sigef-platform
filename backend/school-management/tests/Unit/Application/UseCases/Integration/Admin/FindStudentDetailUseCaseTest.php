<?php

namespace Tests\Unit\Application\UseCases\Integration\Admin;

use App\Core\Application\UseCases\Admin\StudentManagement\FindStudentDetailUseCase;
use App\Core\Domain\Entities\StudentDetail;
use App\Exceptions\NotFound\StudentDetailNotFoundException;
use App\Models\Career;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\StudentDetail as StudentDetailModel;

class FindStudentDetailUseCaseTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_returns_student_detail_when_found(): void
    {
        // Arrange
        $user = User::factory()->create();
        $career = Career::factory()->create();
        $studentDetail = StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id,
        ]);

        $useCase = app(FindStudentDetailUseCase::class);

        // Act
        $result = $useCase->execute($user->id);

        // Assert
        $this->assertInstanceOf(StudentDetail::class, $result);
        $this->assertEquals($studentDetail->user_id, $result->user_id);
        $this->assertEquals($studentDetail->career_id, $result->career_id);
        $this->assertEquals($studentDetail->n_control, $result->n_control);
        $this->assertEquals($studentDetail->semestre, $result->semestre);
        $this->assertEquals($studentDetail->group, $result->group);
        $this->assertEquals($studentDetail->workshop, $result->workshop);
    }

    #[Test]
    public function it_throws_exception_when_student_detail_not_found(): void
    {
        // Arrange
        $user = User::factory()->create();
        // No crear StudentDetail para este usuario

        $useCase = app(FindStudentDetailUseCase::class);

        // Assert
        $this->expectException(StudentDetailNotFoundException::class);

        // Act
        $useCase->execute($user->id);
    }

    #[Test]
    public function it_throws_exception_for_non_existent_user(): void
    {
        // Arrange
        $nonExistentUserId = 99999;
        $useCase = app(FindStudentDetailUseCase::class);

        // Assert
        $this->expectException(StudentDetailNotFoundException::class);

        // Act
        $useCase->execute($nonExistentUserId);
    }

    #[Test]
    public function it_returns_complete_student_detail_object(): void
    {
        // Arrange
        $user = User::factory()->create();
        $career = Career::factory()->create();
        $studentDetail = StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id,
            'n_control' => '20230001',
            'semestre' => 5,
            'group' => 'A',
            'workshop' => 'Taller de Programación'
        ]);

        $useCase = app(FindStudentDetailUseCase::class);

        // Act
        $result = $useCase->execute($user->id);

        // Assert
        $this->assertInstanceOf(StudentDetail::class, $result);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals($career->id, $result->career_id);
        $this->assertEquals('20230001', $result->n_control);
        $this->assertEquals(5, $result->semestre);
        $this->assertEquals('A', $result->group);
        $this->assertEquals('Taller de Programación', $result->workshop);
    }

    #[Test]
    public function it_handles_nullable_fields_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();
        $studentDetail = StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'career_id' => null,
            'n_control' => null,
            'semestre' => null,
            'group' => null,
            'workshop' => null
        ]);

        $useCase = app(FindStudentDetailUseCase::class);

        // Act
        $result = $useCase->execute($user->id);

        // Assert
        $this->assertInstanceOf(StudentDetail::class, $result);
        $this->assertNull($result->career_id);
        $this->assertNull($result->n_control);
        $this->assertNull($result->semestre);
        $this->assertNull($result->group);
        $this->assertNull($result->workshop);
    }

    #[Test]
    public function it_handles_empty_string_fields(): void
    {
        // Arrange
        $user = User::factory()->create();
        $studentDetail = StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'n_control' => '',
            'group' => '',
            'workshop' => ''
        ]);

        $useCase = app(FindStudentDetailUseCase::class);

        // Act
        $result = $useCase->execute($user->id);

        // Assert
        $this->assertInstanceOf(StudentDetail::class, $result);
        $this->assertEquals('', $result->n_control);
        $this->assertEquals('', $result->group);
        $this->assertEquals('', $result->workshop);
    }

    #[Test]
    public function it_returns_different_student_details_for_different_users(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $studentDetail1 = StudentDetailModel::factory()->create([
            'user_id' => $user1->id,
            'n_control' => '20230001'
        ]);

        $studentDetail2 = StudentDetailModel::factory()->create([
            'user_id' => $user2->id,
            'n_control' => '20230002'
        ]);

        $useCase = app(FindStudentDetailUseCase::class);

        // Act
        $result1 = $useCase->execute($user1->id);
        $result2 = $useCase->execute($user2->id);

        // Assert
        $this->assertInstanceOf(StudentDetail::class, $result1);
        $this->assertInstanceOf(StudentDetail::class, $result2);
        $this->assertEquals('20230001', $result1->n_control);
        $this->assertEquals('20230002', $result2->n_control);
        $this->assertNotEquals($result1->n_control, $result2->n_control);
    }

    #[Test]
    public function it_works_with_negative_user_id(): void
    {
        // Arrange
        $useCase = app(FindStudentDetailUseCase::class);

        // Assert
        $this->expectException(StudentDetailNotFoundException::class);

        // Act
        $useCase->execute(-1);
    }

    #[Test]
    public function it_works_with_zero_user_id(): void
    {
        // Arrange
        $useCase = app(FindStudentDetailUseCase::class);

        // Assert
        $this->expectException(StudentDetailNotFoundException::class);

        // Act
        $useCase->execute(0);
    }

    #[Test]
    public function it_handles_database_isolation_correctly(): void
    {
        // Arrange
        $user = User::factory()->create();
        $studentDetail = StudentDetailModel::factory()->create(['user_id' => $user->id]);

        $useCase = app(FindStudentDetailUseCase::class);

        // Act - Primera búsqueda exitosa
        $result1 = $useCase->execute($user->id);
        $this->assertInstanceOf(StudentDetail::class, $result1);

        // Eliminar el StudentDetail
        $studentDetail->delete();

        // Assert - Segunda búsqueda debería fallar
        $this->expectException(StudentDetailNotFoundException::class);

        // Act - Segunda búsqueda
        $useCase->execute($user->id);
    }

    #[Test]
    public function it_preserves_student_detail_immutability(): void
    {
        // Arrange
        $user = User::factory()->create();
        $studentDetail = StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'n_control' => '20230001'
        ]);

        $useCase = app(FindStudentDetailUseCase::class);

        // Act - Obtener el StudentDetail por primera vez
        $studentDetail1 = $useCase->execute($user->id);

        // Modificar directamente en la base de datos
        $newNControl = '20230099';
        $newSemestre = 6;
        StudentDetailModel::where('user_id', $user->id)->update([
            'n_control' => $newNControl,
            'semestre' => $newSemestre
        ]);

        // Obtener el StudentDetail nuevamente
        $studentDetail2 = $useCase->execute($user->id);

        // Assert - El primer objeto debería mantener sus valores originales
        $this->assertEquals('20230001', $studentDetail1->n_control);
        $this->assertNotEquals($newNControl, $studentDetail1->n_control);

        // El segundo objeto debería tener los nuevos valores
        $this->assertEquals($newNControl, $studentDetail2->n_control);
        $this->assertEquals($newSemestre, $studentDetail2->semestre);
    }

    #[Test]
    public function it_validates_all_student_detail_properties(): void
    {
        // Arrange
        $user = User::factory()->create();
        $career = Career::factory()->create();
        $studentDetail = StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id,
            'n_control' => '20230001',
            'semestre' => 5,
            'group' => 'B',
            'workshop' => 'Taller de Software'
        ]);

        $useCase = app(FindStudentDetailUseCase::class);

        // Act
        $result = $useCase->execute($user->id);

        // Assert - Verificar todas las propiedades
        $this->assertInstanceOf(StudentDetail::class, $result);
        $this->assertIsInt($result->user_id);
        $this->assertIsInt($result->career_id);
        $this->assertIsString($result->n_control);
        $this->assertIsInt($result->semestre);
        $this->assertIsString($result->group);
        $this->assertIsString($result->workshop);

        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals($career->id, $result->career_id);
        $this->assertEquals('20230001', $result->n_control);
        $this->assertEquals(5, $result->semestre);
        $this->assertEquals('B', $result->group);
        $this->assertEquals('Taller de Software', $result->workshop);
    }

    #[Test]
    public function it_handles_special_characters_in_fields(): void
    {
        // Arrange
        $user = User::factory()->create();
        $career = Career::factory()->create();
        $studentDetail = StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id,
            'n_control' => '2023-0001-ABC',
            'group' => 'B-1',
            'workshop' => 'Taller de Ingeniería'
        ]);

        $useCase = app(FindStudentDetailUseCase::class);

        // Act
        $result = $useCase->execute($user->id);

        // Assert
        $this->assertInstanceOf(StudentDetail::class, $result);
        $this->assertEquals('2023-0001-ABC', $result->n_control);
        $this->assertEquals('B-1', $result->group);
        $this->assertEquals('Taller de Ingeniería', $result->workshop);
    }

    #[Test]
    public function it_finds_student_detail_after_user_deletion_scenario(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $studentDetail1 = StudentDetailModel::factory()->create(['user_id' => $user1->id]);
        $studentDetail2 = StudentDetailModel::factory()->create(['user_id' => $user2->id]);

        $useCase = app(FindStudentDetailUseCase::class);

        // Act & Assert - Ambos deberían encontrarse
        $result1 = $useCase->execute($user1->id);
        $result2 = $useCase->execute($user2->id);

        $this->assertInstanceOf(StudentDetail::class, $result1);
        $this->assertInstanceOf(StudentDetail::class, $result2);

        // Eliminar usuario 1
        $user1->delete();

        // User 2 debería seguir encontrándose
        $result2After = $useCase->execute($user2->id);
        $this->assertInstanceOf(StudentDetail::class, $result2After);

        // User 1 debería lanzar excepción
        $this->expectException(StudentDetailNotFoundException::class);
        $useCase->execute($user1->id);
    }

    #[Test]
    public function it_works_with_maximum_field_lengths(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Crear valores en los límites de longitud
        $longNControl = str_repeat('A', 20); // Ejemplo: 20 caracteres
        $longGroup = str_repeat('B', 10); // Ejemplo: 10 caracteres
        $longWorkshop = str_repeat('C', 100); // Ejemplo: 100 caracteres

        $studentDetail = StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'n_control' => $longNControl,
            'group' => $longGroup,
            'workshop' => $longWorkshop
        ]);

        $useCase = app(FindStudentDetailUseCase::class);

        // Act
        $result = $useCase->execute($user->id);

        // Assert
        $this->assertInstanceOf(StudentDetail::class, $result);
        $this->assertEquals($longNControl, $result->n_control);
        $this->assertEquals($longGroup, $result->group);
        $this->assertEquals($longWorkshop, $result->workshop);
        $this->assertEquals(strlen($longNControl), strlen($result->n_control));
        $this->assertEquals(strlen($longGroup), strlen($result->group));
        $this->assertEquals(strlen($longWorkshop), strlen($result->workshop));
    }

    #[Test]
    public function it_returns_id_field_when_present(): void
    {
        // Arrange
        $user = User::factory()->create();
        $studentDetail = StudentDetailModel::factory()->create(['user_id' => $user->id]);

        $useCase = app(FindStudentDetailUseCase::class);

        // Act
        $result = $useCase->execute($user->id);

        // Assert
        $this->assertInstanceOf(StudentDetail::class, $result);
        $this->assertEquals($studentDetail->id, $result->id);
    }

    #[Test]
    public function it_handles_semestre_as_integer(): void
    {
        // Arrange
        $user = User::factory()->create();
        $studentDetail = StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'semestre' => 8 // Semestre alto
        ]);

        $useCase = app(FindStudentDetailUseCase::class);

        // Act
        $result = $useCase->execute($user->id);

        // Assert
        $this->assertInstanceOf(StudentDetail::class, $result);
        $this->assertIsInt($result->semestre);
        $this->assertEquals(8, $result->semestre);
    }

}
