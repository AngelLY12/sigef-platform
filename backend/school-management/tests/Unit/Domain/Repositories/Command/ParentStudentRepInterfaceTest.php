<?php

namespace Tests\Unit\Domain\Repositories\Command;

use Tests\Stubs\Repositories\Command\ParentStudentRepStub;
use Tests\Unit\Domain\Repositories\BaseRepositoryTestCase;
use App\Core\Domain\Repositories\Command\User\ParentStudentRepInterface;
use App\Core\Domain\Entities\ParentStudent;
use App\Core\Domain\Enum\User\RelationshipType;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ParentStudentRepInterfaceTest extends BaseRepositoryTestCase
{
    /**
     * The interface class being implemented
     */
    protected string $interfaceClass = ParentStudentRepInterface::class;

    /**
     * Setup the repository instance for testing
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Usamos un stub para probar el contrato
        $this->repository = new ParentStudentRepStub();
        $this->repository->clearRelations();

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

        $methods = [
            'create',
            'update',
            'delete'
        ];

        foreach ($methods as $method) {
            $this->assertMethodExists($method);
        }
    }

    #[Test]
    public function it_can_create_parent_student_relation(): void
    {
        $relation = new ParentStudent(
            parentId: 50,
            studentId: 500,
            parentRoleId: 3,
            studentRoleId: 4,
            relationship: RelationshipType::PADRE
        );

        $result = $this->repository->create($relation);

        $this->assertInstanceOf(ParentStudent::class, $result);
        $this->assertEquals(50, $result->parentId);
        $this->assertEquals(500, $result->studentId);
        $this->assertEquals(3, $result->parentRoleId);
        $this->assertEquals(4, $result->studentRoleId);
        $this->assertEquals(RelationshipType::PADRE, $result->relationship);
    }

    #[Test]
    public function created_relation_can_have_null_relationship(): void
    {
        $relation = new ParentStudent(
            parentId: 51,
            studentId: 501,
            parentRoleId: 3,
            studentRoleId: 4,
            relationship: null
        );

        $result = $this->repository->create($relation);

        $this->assertInstanceOf(ParentStudent::class, $result);
        $this->assertNull($result->relationship);
    }

    #[Test]
    public function it_can_update_parent_student_relation(): void
    {
        // Primero crear una relación
        $relation = new ParentStudent(
            parentId: 5,
            studentId: 20,
            parentRoleId: 3,
            studentRoleId: 4,
            relationship: RelationshipType::PADRE
        );
        $created = $this->repository->create($relation);

        // Actualizar la relación
        $fields = [
            'relationship' => RelationshipType::TUTOR_LEGAL
        ];

        $result = $this->repository->update(5, 20, $fields);

        $this->assertInstanceOf(ParentStudent::class, $result);
        $this->assertEquals(5, $result->parentId);
        $this->assertEquals(20, $result->studentId);
        $this->assertEquals(RelationshipType::TUTOR_LEGAL, $result->relationship);
    }

    #[Test]
    public function update_throws_exception_when_relation_not_found(): void
    {
        $parentId = 999;
        $studentId = 888;
        $fields = ['relationship' => RelationshipType::MADRE];

        $this->expectException(ModelNotFoundException::class);

        $this->repository->update($parentId, $studentId, $fields);
    }

    #[Test]
    public function update_can_handle_partial_fields(): void
    {
        // Primero crear una relación
        $relation = new ParentStudent(
            parentId: 6,
            studentId: 21,
            parentRoleId: 3,
            studentRoleId: 4,
            relationship: RelationshipType::PADRE
        );
        $created = $this->repository->create($relation);

        // Actualizar solo relationship
        $fields = ['relationship' => RelationshipType::MADRE];
        $result = $this->repository->update(6, 21, $fields);

        $this->assertEquals(RelationshipType::MADRE, $result->relationship);
        // Los otros campos deben permanecer igual
        $this->assertEquals(6, $result->parentId);
        $this->assertEquals(21, $result->studentId);
    }

    #[Test]
    public function it_can_delete_parent_student_relation(): void
    {
        // Primero crear una relación
        $relation = new ParentStudent(
            parentId: 7,
            studentId: 22,
            parentRoleId: 3,
            studentRoleId: 4,
            relationship: RelationshipType::TUTOR
        );
        $created = $this->repository->create($relation);

        // Eliminar la relación
        $this->repository->delete(7, 22);

        // Verificar que ya no existe
        $stub = $this->repository;
        $this->expectException(ModelNotFoundException::class);

        $stub->update(7, 22, ['relationship' => RelationshipType::PADRE]);
    }

    #[Test]
    public function delete_throws_exception_when_relation_not_found(): void
    {
        $parentId = 999;
        $studentId = 888;

        $this->expectException(ModelNotFoundException::class);

        $this->repository->delete($parentId, $studentId);
    }

    #[Test]
    public function relationship_type_enum_values(): void
    {
        $this->assertEquals('padre', RelationshipType::PADRE->value);
        $this->assertEquals('madre', RelationshipType::MADRE->value);
        $this->assertEquals('tutor', RelationshipType::TUTOR->value);
        $this->assertEquals('tutor_legal', RelationshipType::TUTOR_LEGAL->value);
    }

    #[Test]
    public function it_handles_database_errors_gracefully(): void
    {
        $stub = new ParentStudentRepStub();
        $stub->shouldThrowDatabaseError(true);

        $relation = new ParentStudent(
            parentId: 8,
            studentId: 23,
            parentRoleId: 3,
            studentRoleId: 4,
            relationship: RelationshipType::PADRE
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database error');

        $stub->create($relation);
    }

    #[Test]
    public function it_can_handle_multiple_relations(): void
    {
        $stub = new ParentStudentRepStub();

        // 1. Crear relación padre-hijo
        $relation1 = $stub->create(new ParentStudent(10, 30, 3, 4, RelationshipType::PADRE));

        // 2. Crear relación madre-hijo
        $relation2 = $stub->create(new ParentStudent(11, 30, 3, 4, RelationshipType::MADRE));

        // 3. Actualizar la primera relación
        $updated = $stub->update(10, 30, ['relationship' => RelationshipType::TUTOR_LEGAL]);
        $this->assertEquals(RelationshipType::TUTOR_LEGAL, $updated->relationship);

        // 4. Eliminar la segunda relación
        $stub->delete(11, 30);

        // 5. Verificar que la segunda ya no existe
        $this->expectException(ModelNotFoundException::class);
        $stub->update(11, 30, ['relationship' => RelationshipType::PADRE]);

        // 6. La primera debería seguir existiendo
        $existing = $stub->getRelation(10, 30);
        $this->assertNotNull($existing);
        $this->assertEquals(RelationshipType::TUTOR_LEGAL, $existing->relationship);
    }

    #[Test]
    public function user_status_enum_transitions(): void
    {
        // Test de transiciones de UserStatus (aunque no es parte directa de la interfaz)
        $active = \App\Core\Domain\Enum\User\UserStatus::ACTIVO;
        $baja = \App\Core\Domain\Enum\User\UserStatus::BAJA;
        $bajaTemporal = \App\Core\Domain\Enum\User\UserStatus::BAJA_TEMPORAL;
        $eliminado = \App\Core\Domain\Enum\User\UserStatus::ELIMINADO;

        // Test allowedTransitions
        $this->assertContains($baja, $active->allowedTransitions());
        $this->assertContains($bajaTemporal, $active->allowedTransitions());
        $this->assertContains($eliminado, $active->allowedTransitions());

        // Test canTransitionTo
        $this->assertTrue($active->canTransitionTo($baja));
        $this->assertTrue($baja->canTransitionTo($active));
        $this->assertFalse($baja->canTransitionTo($bajaTemporal)); // No está en allowedTransitions

        // Test isUpdatable
        $this->assertTrue($active->isUpdatable());
        $this->assertFalse($baja->isUpdatable());
        $this->assertFalse($bajaTemporal->isUpdatable());
        $this->assertFalse($eliminado->isUpdatable());
    }
}
