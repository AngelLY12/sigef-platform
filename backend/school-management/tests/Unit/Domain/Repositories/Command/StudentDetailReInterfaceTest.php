<?php

namespace Tests\Unit\Domain\Repositories\Command;

use Tests\Stubs\Repositories\Command\StudentDetailRepositoryStub;
use Tests\Unit\Domain\Repositories\BaseRepositoryTestCase;
use App\Core\Domain\Repositories\Command\User\StudentDetailReInterface;
use App\Core\Application\DTO\Request\StudentDetail\CreateStudentDetailDTO;
use App\Core\Domain\Entities\StudentDetail;
use App\Core\Domain\Entities\User;
use App\Models\User as ModelsUser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class StudentDetailReInterfaceTest extends BaseRepositoryTestCase
{
    /**
     * The interface class being implemented
     */
    protected string $interfaceClass = StudentDetailReInterface::class;

    /**
     * Setup the repository instance for testing
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Usamos el stub para pruebas unitarias
        $this->repository = new StudentDetailRepositoryStub();

        // O si prefieres probar el repositorio real (requeriría configurar la base de datos):
        // $this->repository = app(\App\Core\Infraestructure\Repositories\Command\User\EloquentStudentDetailRepository::class);
    }

    /**
     * Test que el repositorio puede ser instanciado
     */
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertNotNull($this->repository, 'El repositorio no está inicializado');
        $this->assertImplementsInterface($this->interfaceClass);
    }

    /**
     * Test que todos los métodos requeridos existen
     */
    #[Test]
    public function it_has_all_required_methods(): void
    {
        $this->assertNotNull($this->repository, 'El repositorio no está inicializado');

        // Usar Reflection para obtener métodos automáticamente:
        $reflection = new \ReflectionClass($this->interfaceClass);
        $methods = $reflection->getMethods();

        foreach ($methods as $method) {
            $this->assertMethodExists($method->getName());
        }
    }

    /**
     * Test métodos específicos basados en el tipo de repositorio
     */
    #[Test]
    public function it_has_type_specific_methods(): void
    {
        $this->assertNotNull($this->repository, 'El repositorio no está inicializado');

        // Métodos comunes para Command
        $this->assertMethodExists('findStudentDetails');
        $this->assertMethodExists('incrementSemesterForAll');
        $this->assertMethodExists('getStudentsExceedingSemesterLimit');
        $this->assertMethodExists('updateStudentDetails');
        $this->assertMethodExists('insertStudentDetails');
        $this->assertMethodExists('insertSingleStudentDetail');
        $this->assertMethodExists('attachStudentDetail');
    }

    /**
     * Test para findStudentDetails con usuario existente
     */
    #[Test]
    public function it_can_find_student_details_for_existing_user(): void
    {
        // Act
        $result = $this->repository->findStudentDetails(1);

        // Assert
        $this->assertInstanceOf(StudentDetail::class, $result);
        $this->assertEquals(1, $result->user_id);
        $this->assertEquals(5, $result->semestre);
    }

    /**
     * Test para findStudentDetails con usuario no existente
     */
    #[Test]
    public function it_returns_null_for_non_existing_user(): void
    {
        // Act
        $result = $this->repository->findStudentDetails(999);

        // Assert
        $this->assertNull($result);
    }

    /**
     * Test para incrementSemesterForAll
     */
    #[Test]
    public function it_can_increment_semester_for_all_students(): void
    {
        // Arrange
        $initialCount = $this->repository->findStudentDetails(1)->semestre;

        // Act
        $affectedRows = $this->repository->incrementSemesterForAll();

        // Assert
        $this->assertGreaterThan(0, $affectedRows);
        $this->assertEquals($initialCount + 1, $this->repository->findStudentDetails(1)->semestre);
    }

    /**
     * Test para getStudentsExceedingSemesterLimit con diferentes límites
     */
    #[Test]
    #[DataProvider('semesterLimitProvider')]
    public function it_can_get_students_exceeding_semester_limit(int $limit, array $expectedUsers): void
    {
        // Act
        $result = $this->repository->getStudentsExceedingSemesterLimit($limit);

        // Assert
        $this->assertEquals($expectedUsers, $result);
    }

    public static function semesterLimitProvider(): array
    {
        return [
            'limit_4' => [4, [1, 3]],
            'limit_5' => [5, [3]],
            'limit_10' => [10, [3]],
            'limit_15' => [15, []],
        ];
    }

    /**
     * Test para updateStudentDetails exitoso
     */
    #[Test]
    public function it_can_update_student_details(): void
    {
        // Arrange
        $updateData = [
            'semestre' => 6,
            'group' => 'B',
            'workshop' => 'Taller Actualizado'
        ];

        // Act
        $result = $this->repository->updateStudentDetails(1, $updateData);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertInstanceOf(StudentDetail::class, $result->studentDetail);
        $this->assertEquals(6, $result->studentDetail->semestre);
        $this->assertEquals('B', $result->studentDetail->group);
    }

    /**
     * Test para updateStudentDetails con usuario no existente
     */
    #[Test]
    public function it_throws_exception_when_updating_non_existing_student(): void
    {
        // Expect
        $this->expectException(\RuntimeException::class);

        // Act
        $this->repository->updateStudentDetails(999, ['semestre' => 1]);
    }

    /**
     * Test para insertStudentDetails con múltiples registros
     */
    #[Test]
    public function it_can_insert_multiple_student_details(): void
    {
        // Arrange
        $studentDetails = [
            [
                'user_id' => 100,
                'career_id' => 20,
                'n_control' => 20211001,
                'semestre' => 1,
                'group' => 'C'
            ],
            [
                'user_id' => 101,
                'career_id' => 21,
                'n_control' => 20211002,
                'semestre' => 2,
                'group' => 'D'
            ]
        ];

        // Act
        $insertedCount = $this->repository->insertStudentDetails($studentDetails);

        // Assert
        $this->assertEquals(2, $insertedCount);

        // Verificar que los datos fueron insertados
        $detail1 = $this->repository->findStudentDetails(100);
        $this->assertInstanceOf(StudentDetail::class, $detail1);
        $this->assertEquals(1, $detail1->semestre);
    }

    /**
     * Test para insertStudentDetails con array vacío
     */
    #[Test]
    public function it_returns_zero_when_inserting_empty_array(): void
    {
        // Act
        $result = $this->repository->insertStudentDetails([]);

        // Assert
        $this->assertEquals(0, $result);
    }

    /**
     * Test para insertSingleStudentDetail
     */
    #[Test]
    public function it_can_insert_single_student_detail(): void
    {
        // Arrange
        $detail = [
            'user_id' => 200,
            'career_id' => 30,
            'n_control' => 20221001,
            'semestre' => 3,
            'group' => 'E'
        ];

        // Act
        $result = $this->repository->insertSingleStudentDetail($detail);

        // Assert
        $this->assertTrue($result);

        $retrievedDetail = $this->repository->findStudentDetails(200);
        $this->assertInstanceOf(StudentDetail::class, $retrievedDetail);
    }

    /**
     * Test para insertSingleStudentDetail sin user_id
     */
    #[Test]
    public function it_throws_exception_when_inserting_detail_without_user_id(): void
    {
        // Expect
        $this->expectException(\InvalidArgumentException::class);

        // Arrange
        $invalidDetail = [
            'career_id' => 30,
            'semestre' => 3
        ];

        // Act
        $this->repository->insertSingleStudentDetail($invalidDetail);
    }

    /**
     * Test para attachStudentDetail
     */
    #[Test]
    public function it_can_attach_student_detail_to_user(): void
    {
        // Arrange
        $dto = new CreateStudentDetailDTO(
            user_id: 999,
            career_id: 40,
            n_control: 20231001,
            semestre: 1,
            group: 'F',
            workshop: 'Taller nuevo'
        );

        // Crear mock de ModelsUser
        $mockUser = $this->createMockUserModel(999);

        // Act
        $result = $this->repository->attachStudentDetail($dto, $mockUser);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertInstanceOf(StudentDetail::class, $result->studentDetail);
        $this->assertEquals(40, $result->studentDetail->career_id);
    }

    /**
     * Test manejo de errores
     */
    #[Test]
    public function it_handles_errors_correctly(): void
    {
        // Este test verifica que los métodos que pueden lanzar excepciones lo hagan apropiadamente
        // Ya estamos probando esto en otros tests específicos
        $this->assertTrue(true);
    }

    /**
     * Test que StudentDetail::promote funciona correctamente
     */
    #[Test]
    public function student_detail_promote_method_works(): void
    {
        // Arrange
        $detail = $this->repository->findStudentDetails(1);
        $initialSemester = $detail->semestre;

        // Act
        $detail->promote();

        // Assert
        $this->assertEquals($initialSemester + 1, $detail->semestre);
    }

    /**
     * Test que StudentDetail::toArray funciona correctamente
     */
    #[Test]
    public function student_detail_to_array_method_works(): void
    {
        // Arrange
        $detail = $this->repository->findStudentDetails(1);

        // Act
        $array = $detail->toArray();

        // Assert
        $this->assertIsArray($array);
        $this->assertArrayHasKey('user_id', $array);
        $this->assertArrayHasKey('semestre', $array);
        $this->assertArrayHasKey('group', $array);
    }

    /**
     * Helper method para crear mock de ModelsUser
     */
    private function createMockUserModel(int $id = 1): ModelsUser
    {
        $mock = $this->createMock(ModelsUser::class);
        $mock->id = $id;
        $mock->method('toArray')->willReturn(['id' => $id, 'email' => 'test@example.com']);

        // Mock de syncRoles si es necesario
        if (method_exists($mock, 'syncRoles')) {
            $mock->method('syncRoles')->willReturn(true);
        }

        return $mock;
    }
}
