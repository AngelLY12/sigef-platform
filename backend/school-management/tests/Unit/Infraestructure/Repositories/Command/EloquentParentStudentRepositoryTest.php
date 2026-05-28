<?php

namespace Tests\Unit\Infraestructure\Repositories\Command;

use App\Core\Application\Mappers\ParentStudentMapper;
use App\Core\Domain\Entities\ParentStudent;
use App\Models\ParentStudent as EloquentParentStudent;
use App\Core\Domain\Enum\User\RelationshipType;
use App\Core\Infraestructure\Repositories\Command\User\EloquentParentStudentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EloquentParentStudentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentParentStudentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentParentStudentRepository();
    }

    #[Test]
    public function create_parent_student_relation_successfully(): void
    {
        // Arrange
        // DEJAR QUE EL FACTORY CREE LOS ROLES AUTOMÁTICAMENTE
        $relationData = \App\Models\ParentStudent::factory()->make();

        // Usar los datos generados por el factory
        $domainRelation = ParentStudentMapper::toDomain($this->toArray($relationData));

        // Act
        $result = $this->repository->create($domainRelation);

        // Assert
        $this->assertInstanceOf(ParentStudent::class, $result);
        $this->assertEquals($relationData->parent_id, $result->parentId);
        $this->assertEquals($relationData->student_id, $result->studentId);
        $this->assertEquals($relationData->parent_role_id, $result->parentRoleId);
        $this->assertEquals($relationData->student_role_id, $result->studentRoleId);
        $this->assertEquals($relationData->relationship, $result->relationship);

        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $relationData->parent_id,
            'student_id' => $relationData->student_id,
            'parent_role_id' => $relationData->parent_role_id,
            'student_role_id' => $relationData->student_role_id,
            'relationship' => $relationData->relationship->value,
        ]);
    }

    #[Test]
    public function create_parent_student_with_null_relationship(): void
    {
        // Arrange
        $relationData = \App\Models\ParentStudent::factory()->state([
            'relationship' => null
        ])->make();

        $domainRelation = ParentStudentMapper::toDomain($this->toArray($relationData));

        // Act
        $result = $this->repository->create($domainRelation);

        // Assert
        $this->assertInstanceOf(ParentStudent::class, $result);
        $this->assertEquals($relationData->parent_id, $result->parentId);
        $this->assertEquals($relationData->student_id, $result->studentId);
        $this->assertNull($result->relationship);

        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $relationData->parent_id,
            'student_id' => $relationData->student_id,
            'relationship' => null,
        ]);
    }

    #[Test]
    public function create_parent_student_with_factory(): void
    {
        // Arrange
        // EL FACTORY MANEJA LOS ROLES AUTOMÁTICAMENTE
        $relationData = \App\Models\ParentStudent::factory()->make();
        $domainRelation = ParentStudentMapper::toDomain($this->toArray($relationData));

        // Act
        $result = $this->repository->create($domainRelation);

        // Assert
        $this->assertInstanceOf(ParentStudent::class, $result);
        $this->assertEquals($relationData->parent_id, $result->parentId);
        $this->assertEquals($relationData->student_id, $result->studentId);
        $this->assertEquals($relationData->parent_role_id, $result->parentRoleId);
        $this->assertEquals($relationData->student_role_id, $result->studentRoleId);
        $this->assertEquals($relationData->relationship, $result->relationship);

        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $relationData->parent_id,
            'student_id' => $relationData->student_id,
        ]);
    }

    #[Test]
    public function create_multiple_relations_for_same_parent(): void
    {
        // Arrange
        // Crear un padre primero
        $parent = \App\Models\User::factory()->create();

        // Crear dos estudiantes
        $student1 = \App\Models\User::factory()->create();
        $student2 = \App\Models\User::factory()->create();

        // Usar el factory con forParent() y forStudent() que manejan los roles
        $relationData1 = \App\Models\ParentStudent::factory()
            ->forParent($parent)
            ->forStudent($student1)
            ->make();

        $relationData2 = \App\Models\ParentStudent::factory()
            ->forParent($parent)
            ->forStudent($student2)
            ->make();

        $domainRelation1 = ParentStudentMapper::toDomain($this->toArray($relationData1));
        $domainRelation2 = ParentStudentMapper::toDomain($this->toArray($relationData2));

        // Act
        $result1 = $this->repository->create($domainRelation1);
        $result2 = $this->repository->create($domainRelation2);

        // Assert
        $this->assertNotEquals($result1->studentId, $result2->studentId);

        $relationCount = EloquentParentStudent::where('parent_id', $parent->id)->count();
        $this->assertEquals(2, $relationCount);
    }

    #[Test]
    public function create_multiple_relations_for_same_student(): void
    {
        // Arrange
        // Crear un estudiante primero
        $student = \App\Models\User::factory()->create();

        // Crear dos padres
        $parent1 = \App\Models\User::factory()->create();
        $parent2 = \App\Models\User::factory()->create();

        // Usar el factory con forParent() y forStudent()
        $relationData1 = \App\Models\ParentStudent::factory()
            ->forParent($parent1)
            ->forStudent($student)
            ->state(['relationship' => RelationshipType::PADRE])
            ->make();

        $relationData2 = \App\Models\ParentStudent::factory()
            ->forParent($parent2)
            ->forStudent($student)
            ->state(['relationship' => RelationshipType::MADRE])
            ->make();

        $domainRelation1 = ParentStudentMapper::toDomain($this->toArray($relationData1));
        $domainRelation2 = ParentStudentMapper::toDomain($this->toArray($relationData2));

        // Act
        $result1 = $this->repository->create($domainRelation1);
        $result2 = $this->repository->create($domainRelation2);

        // Assert
        $this->assertNotEquals($result1->parentId, $result2->parentId);
        $this->assertEquals(RelationshipType::PADRE, $result1->relationship);
        $this->assertEquals(RelationshipType::MADRE, $result2->relationship);

        $relationCount = EloquentParentStudent::where('student_id', $student->id)->count();
        $this->assertEquals(2, $relationCount);
    }

    #[Test]
    public function cannot_create_duplicate_parent_student_relation(): void
    {
        // Arrange
        /** @var \App\Models\ParentStudent $relationData */
        $relationData = \App\Models\ParentStudent::factory()->make();
        $domainRelation = ParentStudentMapper::toDomain($this->toArray($relationData));

        // Act - Create first time
        $this->repository->create($domainRelation);

        // Assert - Try to create duplicate (should fail with unique constraint)
        // Depende de si tu tabla tiene restricción única en (parent_id, student_id)
        try {
            $this->repository->create($domainRelation);
            $this->fail('Should have thrown unique constraint exception');
        } catch (\Exception $e) {
            $this->assertTrue(
                str_contains($e->getMessage(), 'Duplicate') ||
                $e instanceof \Illuminate\Database\QueryException
            );
        }

        // Debería haber solo un registro
        $relationCount = EloquentParentStudent::where('parent_id', $relationData->parent_id)
            ->where('student_id', $relationData->student_id)
            ->count();
        $this->assertEquals(1, $relationCount);
    }

    // ==================== UPDATE TESTS ====================

    #[Test]
    public function update_parent_student_relation_successfully(): void
    {
        // Arrange
        // Crear la relación usando el factory (maneja roles automáticamente)
        $relation = \App\Models\ParentStudent::factory()->create([
            'relationship' => RelationshipType::PADRE
        ]);

        $updateData = [
            'relationship' => RelationshipType::MADRE,
        ];

        // Act
        $result = $this->repository->update($relation->parent_id, $relation->student_id, $updateData);

        // Assert
        $this->assertInstanceOf(ParentStudent::class, $result);
        $this->assertEquals($relation->parent_id, $result->parentId);
        $this->assertEquals($relation->student_id, $result->studentId);
        $this->assertEquals(RelationshipType::MADRE, $result->relationship);

        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $relation->parent_id,
            'student_id' => $relation->student_id,
            'relationship' => RelationshipType::MADRE->value,
        ]);
    }

    #[Test]
    public function update_parent_student_set_null_relationship(): void
    {
        // Arrange
        $relation = \App\Models\ParentStudent::factory()->create([
            'relationship' => RelationshipType::PADRE
        ]);

        $updateData = [
            'relationship' => null,
        ];

        // Act
        $result = $this->repository->update($relation->parent_id, $relation->student_id, $updateData);

        // Assert
        $this->assertInstanceOf(ParentStudent::class, $result);
        $this->assertNull($result->relationship);

        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $relation->parent_id,
            'student_id' => $relation->student_id,
            'relationship' => null,
        ]);
    }

    #[Test]
    public function update_parent_student_with_empty_array_does_not_change(): void
    {
        // Arrange
        $relation = \App\Models\ParentStudent::factory()->create([
            'relationship' => RelationshipType::PADRE
        ]);

        $originalRelationship = $relation->relationship;

        // Act
        $result = $this->repository->update($relation->parent_id, $relation->student_id, []);

        // Assert
        $this->assertEquals($originalRelationship, $result->relationship);

        $relation->refresh();
        $this->assertEquals($originalRelationship, $relation->relationship);
    }

    #[Test]
    public function update_nonexistent_parent_student_relation_throws_exception(): void
    {
        // Arrange
        $nonExistentParentId = 999;
        $nonExistentStudentId = 888;
        $updateData = ['relationship' => RelationshipType::PADRE];

        // Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $this->repository->update($nonExistentParentId, $nonExistentStudentId, $updateData);
    }

    #[Test]
    public function update_only_specific_relation_with_multiple_existing(): void
    {
        // Arrange
        // Crear 3 relaciones usando el factory
        $relation1 = \App\Models\ParentStudent::factory()->create([
            'relationship' => RelationshipType::PADRE
        ]);

        $relation2 = \App\Models\ParentStudent::factory()->create([
            'relationship' => RelationshipType::MADRE
        ]);

        $relation3 = \App\Models\ParentStudent::factory()->create([
            'relationship' => RelationshipType::TUTOR
        ]);

        // Act - Actualizar solo la segunda relación
        $updateData = ['relationship' => RelationshipType::TUTOR_LEGAL];
        $result = $this->repository->update($relation2->parent_id, $relation2->student_id, $updateData);

        // Assert
        $this->assertEquals(RelationshipType::TUTOR_LEGAL, $result->relationship);

        // Verificar que solo se actualizó la relación 2
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $relation1->parent_id,
            'student_id' => $relation1->student_id,
            'relationship' => RelationshipType::PADRE->value,
        ]);

        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $relation2->parent_id,
            'student_id' => $relation2->student_id,
            'relationship' => RelationshipType::TUTOR_LEGAL->value,
        ]);

        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $relation3->parent_id,
            'student_id' => $relation3->student_id,
            'relationship' => RelationshipType::TUTOR->value,
        ]);
    }

    // ==================== DELETE TESTS ====================

    #[Test]
    public function delete_parent_student_relation_successfully(): void
    {
        // Arrange
        $relation = \App\Models\ParentStudent::factory()->create();

        // Act
        $this->repository->delete($relation->parent_id, $relation->student_id);

        // Assert
        $this->assertDatabaseMissing('parent_student', [
            'parent_id' => $relation->parent_id,
            'student_id' => $relation->student_id,
        ]);
    }

    #[Test]
    public function delete_nonexistent_parent_student_relation_throws_exception(): void
    {
        // Arrange
        $nonExistentParentId = 999;
        $nonExistentStudentId = 888;

        // Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $this->repository->delete($nonExistentParentId, $nonExistentStudentId);
    }

    #[Test]
    public function delete_only_specific_relation_with_multiple_existing(): void
    {
        // Arrange
        // Crear 3 relaciones usando el factory
        $relation1 = \App\Models\ParentStudent::factory()->create();
        $relation2 = \App\Models\ParentStudent::factory()->create();
        $relation3 = \App\Models\ParentStudent::factory()->create();

        // Act - Eliminar solo la segunda relación
        $this->repository->delete($relation2->parent_id, $relation2->student_id);

        // Assert
        $this->assertDatabaseMissing('parent_student', [
            'parent_id' => $relation2->parent_id,
            'student_id' => $relation2->student_id,
        ]);

        // Las otras dos deben seguir existiendo
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $relation1->parent_id,
            'student_id' => $relation1->student_id,
        ]);

        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $relation3->parent_id,
            'student_id' => $relation3->student_id,
        ]);
    }

    #[Test]
    public function delete_relation_does_not_delete_users(): void
    {
        // Arrange
        $relation = \App\Models\ParentStudent::factory()->create();
        $parentId = $relation->parent_id;
        $studentId = $relation->student_id;

        // Act
        $this->repository->delete($parentId, $studentId);

        // Assert
        // La relación debe eliminarse
        $this->assertDatabaseMissing('parent_student', [
            'parent_id' => $parentId,
            'student_id' => $studentId,
        ]);

        // Pero los usuarios deben seguir existiendo
        $this->assertDatabaseHas('users', ['id' => $parentId]);
        $this->assertDatabaseHas('users', ['id' => $studentId]);
    }

    // ==================== COMPREHENSIVE TESTS ====================

    #[Test]
    public function create_update_delete_complete_cycle(): void
    {
        // Test completo del ciclo de vida de relaciones padre-estudiante

        // 1. Crear relación usando el factory
        $relationData = \App\Models\ParentStudent::factory()->make();
        $domainRelation = ParentStudentMapper::toDomain($this->toArray($relationData));

        // Act 1: Create
        $created = $this->repository->create($domainRelation);

        // Assert 1
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $created->parentId,
            'student_id' => $created->studentId,
            'relationship' => $created->relationship?->value,
        ]);

        // Act 2: Update
        $updated = $this->repository->update($created->parentId, $created->studentId, [
            'relationship' => RelationshipType::MADRE
        ]);

        // Assert 2
        $this->assertEquals(RelationshipType::MADRE, $updated->relationship);
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $created->parentId,
            'student_id' => $created->studentId,
            'relationship' => RelationshipType::MADRE->value,
        ]);

        // Act 3: Delete
        $this->repository->delete($created->parentId, $created->studentId);

        // Assert 3
        $this->assertDatabaseMissing('parent_student', [
            'parent_id' => $created->parentId,
            'student_id' => $created->studentId,
        ]);
    }

    #[Test]
    public function multiple_relations_operations_with_factory(): void
    {
        // Usar factory para crear relaciones con diferentes estados
        $relation1 = \App\Models\ParentStudent::factory()->father()->create();
        $relation2 = \App\Models\ParentStudent::factory()->mother()->create();
        $relation3 = \App\Models\ParentStudent::factory()->guardian()->create();

        // Assert initial states
        $this->assertEquals(RelationshipType::PADRE, $relation1->relationship);
        $this->assertEquals(RelationshipType::MADRE, $relation2->relationship);
        $this->assertEquals(RelationshipType::TUTOR, $relation3->relationship);

        // Act 1: Update relation2
        $updated = $this->repository->update($relation2->parent_id, $relation2->student_id, [
            'relationship' => RelationshipType::TUTOR_LEGAL
        ]);

        // Assert 1
        $this->assertEquals(RelationshipType::TUTOR_LEGAL, $updated->relationship);

        // Act 2: Delete relation1
        $this->repository->delete($relation1->parent_id, $relation1->student_id);

        // Assert 2
        $this->assertDatabaseMissing('parent_student', [
            'parent_id' => $relation1->parent_id,
            'student_id' => $relation1->student_id,
        ]);

        // Act 3: Delete relation3
        $this->repository->delete($relation3->parent_id, $relation3->student_id);

        // Final assert
        $remainingCount = EloquentParentStudent::count();
        $this->assertEquals(1, $remainingCount); // Solo debe quedar relation2
    }

    #[Test]
    public function repository_handles_concurrent_operations(): void
    {
        // Arrange - Crear múltiples relaciones
        $relations = [];
        for ($i = 0; $i < 3; $i++) {
            $relations[] = \App\Models\ParentStudent::factory()->create();
        }

        // Act: Operaciones concurrentes
        // Update primera relación
        $this->repository->update($relations[0]->parent_id, $relations[0]->student_id, [
            'relationship' => RelationshipType::TUTOR_LEGAL
        ]);

        // Delete segunda relación
        $this->repository->delete($relations[1]->parent_id, $relations[1]->student_id);

        // Create nueva relación usando factory
        $newRelationData = \App\Models\ParentStudent::factory()->make();
        $newDomain = ParentStudentMapper::toDomain($this->toArray($newRelationData));
        $created = $this->repository->create($newDomain);

        // Assert
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $relations[0]->parent_id,
            'student_id' => $relations[0]->student_id,
            'relationship' => RelationshipType::TUTOR_LEGAL->value,
        ]);

        $this->assertDatabaseMissing('parent_student', [
            'parent_id' => $relations[1]->parent_id,
            'student_id' => $relations[1]->student_id,
        ]);

        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $created->parentId,
            'student_id' => $created->studentId,
        ]);

        $totalCount = EloquentParentStudent::count();
        $this->assertEquals(3, $totalCount); // relation0 + relation2 + newRelation
    }

    #[Test]
    public function relationship_type_enum_handling(): void
    {
        // Test para verificar que todos los tipos de relación funcionan

        $relationshipTypes = [
            RelationshipType::PADRE,
            RelationshipType::MADRE,
            RelationshipType::TUTOR,
            RelationshipType::TUTOR_LEGAL,
        ];

        foreach ($relationshipTypes as $type) {
            // Arrange - Crear relación con tipo específico
            $relation = \App\Models\ParentStudent::factory()->create([
                'relationship' => $type
            ]);

            // Act - Obtener a través del mapper
            $domain = ParentStudentMapper::toDomain($this->toArray($relation));

            // Assert
            $this->assertEquals($type, $domain->relationship);

            // Act - Update con el mismo tipo (solo para probar)
            $updated = $this->repository->update($relation->parent_id, $relation->student_id, [
                'relationship' => $type
            ]);

            // Assert
            $this->assertEquals($type, $updated->relationship);

            // Cleanup
            $this->repository->delete($relation->parent_id, $relation->student_id);
        }


    }
    private function toArray(\App\Models\ParentStudent $parentStudent): array
    {
        return [
            'parentId' => $parentStudent->parent_id,
            'studentId' => $parentStudent->student_id,
            'parentRoleId' => $parentStudent->parent_role_id,
            'studentRoleId' => $parentStudent->student_role_id,
            'relationship' => $parentStudent->relationship->value ?? null
        ];
    }

}
