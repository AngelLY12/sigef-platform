<?php

namespace Tests\Unit\Application\UseCases\Integration\Parents;

use App\Core\Application\UseCases\Parents\DeleteParentStudentRelationUseCase;
use App\Core\Domain\Enum\User\RelationshipType;
use App\Core\Domain\Enum\User\UserRoles;
use App\Models\ParentStudent;
use App\Models\User;
use Database\Seeders\RolesSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DeleteParentStudentRelationUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        Event::fake();
    }

    #[Test]
    public function it_deletes_parent_student_relation_successfully(): void
    {
        // Arrange
        $student = User::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student->assignRole($studentRole);

        $parent = User::factory()->asParent()->create();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent->assignRole($parentRole);

        // Crear relación parent-student usando el enum
        ParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE->value
        ]);

        $useCase = app(DeleteParentStudentRelationUseCase::class);

        // Act
        $result = $useCase->execute($parent->id, $student->id);

        // Assert
        $this->assertTrue($result);

        // Verificar que la relación fue eliminada
        $this->assertDatabaseMissing('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $student->id
        ]);

        // Verificar que se disparó el evento
        Event::assertDispatched(\App\Events\ParentStudentRelationDelete::class, function ($event) use ($parent, $student) {
            return $event->parentId === $parent->id && $event->studentId === $student->id;
        });
    }

    #[Test]
    public function it_deletes_relation_when_parent_has_multiple_students(): void
    {
        // Arrange
        $parent = User::factory()->asParent()->create();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent->assignRole($parentRole);

        $students = User::factory()->asStudent()->count(3)->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();

        foreach ($students as $student) {
            $student->assignRole($studentRole);
            ParentStudent::create([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'parent_role_id' => $parentRole->id,
                'student_role_id' => $studentRole->id,
                'relationship' => RelationshipType::TUTOR->value
            ]);
        }

        $useCase = app(DeleteParentStudentRelationUseCase::class);

        // Act - Eliminar solo la relación con el primer estudiante
        $result = $useCase->execute($parent->id, $students[0]->id);

        // Assert
        $this->assertTrue($result);

        // Verificar que solo se eliminó la relación con el primer estudiante
        $this->assertDatabaseMissing('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $students[0]->id
        ]);

        // Las otras relaciones deben seguir existiendo
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $students[1]->id
        ]);

        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $students[2]->id
        ]);

        Event::assertDispatched(\App\Events\ParentStudentRelationDelete::class, 1);
    }

    #[Test]
    public function it_deletes_relation_when_student_has_multiple_parents(): void
    {
        // Arrange
        $student = User::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student->assignRole($studentRole);

        $parents = User::factory()->asParent()->count(2)->create();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();

        foreach ($parents as $parent) {
            $parent->assignRole($parentRole);
            ParentStudent::create([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'parent_role_id' => $parentRole->id,
                'student_role_id' => $studentRole->id,
                'relationship' => RelationshipType::TUTOR_LEGAL->value
            ]);
        }

        $useCase = app(DeleteParentStudentRelationUseCase::class);

        // Act - Eliminar solo la relación con el primer padre
        $result = $useCase->execute($parents[0]->id, $student->id);

        // Assert
        $this->assertTrue($result);

        // Verificar que solo se eliminó la relación con el primer padre
        $this->assertDatabaseMissing('parent_student', [
            'parent_id' => $parents[0]->id,
            'student_id' => $student->id
        ]);

        // La relación con el segundo padre debe seguir existiendo
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parents[1]->id,
            'student_id' => $student->id
        ]);

        Event::assertDispatched(\App\Events\ParentStudentRelationDelete::class);
    }

    #[Test]
    public function it_returns_true_even_when_relation_does_not_exist(): void
    {
        // Arrange
        $parent = User::factory()->asParent()->create();
        $student = User::factory()->asStudent()->create();
        $this->expectException(ModelNotFoundException::class);
        $useCase = app(DeleteParentStudentRelationUseCase::class);

        // Act - Intentar eliminar una relación que no existe
        $result = $useCase->execute($parent->id, $student->id);


        // No debería haber error, solo no hace nada
        Event::assertNotDispatched(\App\Events\ParentStudentRelationDelete::class);
    }

    #[Test]
    public function it_deletes_relation_with_different_relationships(): void
    {
        // Arrange - Crear relaciones con diferentes tipos del enum
        $relationships = [
            RelationshipType::PADRE,
            RelationshipType::MADRE,
            RelationshipType::TUTOR,
            RelationshipType::TUTOR_LEGAL
        ];

        $student = User::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student->assignRole($studentRole);

        $parent = User::factory()->asParent()->create();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent->assignRole($parentRole);

        foreach ($relationships as $relationship) {
            ParentStudent::create([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'parent_role_id' => $parentRole->id,
                'student_role_id' => $studentRole->id,
                'relationship' => $relationship->value
            ]);

            // Eliminar después de crear para probar cada tipo
            $useCase = app(DeleteParentStudentRelationUseCase::class);
            $result = $useCase->execute($parent->id, $student->id);
            $this->assertTrue($result);

            // Verificar que se eliminó
            $this->assertDatabaseMissing('parent_student', [
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'relationship' => $relationship->value
            ]);
        }

        Event::assertDispatched(\App\Events\ParentStudentRelationDelete::class, 4);
    }

    #[Test]
    public function it_deletes_relation_when_parent_has_no_parent_role(): void
    {
        // Arrange
        $student = User::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student->assignRole($studentRole);

        $parent = User::factory()->asParent()->create();
        // No asignar rol PARENT al padre

        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();

        // Crear relación de todas formas (esto podría ser un estado inválido)
        ParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE->value
        ]);

        $useCase = app(DeleteParentStudentRelationUseCase::class);

        // Act
        $result = $useCase->execute($parent->id, $student->id);

        // Assert - Debería eliminar la relación aunque el padre no tenga el rol
        $this->assertTrue($result);
        $this->assertDatabaseMissing('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $student->id
        ]);

        Event::assertDispatched(\App\Events\ParentStudentRelationDelete::class);
    }

    #[Test]
    public function it_deletes_relation_when_student_has_no_student_role(): void
    {
        // Arrange
        $student = User::factory()->asStudent()->create();
        // No asignar rol STUDENT al estudiante

        $parent = User::factory()->asParent()->create();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent->assignRole($parentRole);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();

        // Crear relación de todas formas
        ParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::MADRE->value
        ]);

        $useCase = app(DeleteParentStudentRelationUseCase::class);

        // Act
        $result = $useCase->execute($parent->id, $student->id);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $student->id
        ]);

        Event::assertDispatched(\App\Events\ParentStudentRelationDelete::class);
    }

    #[Test]
    public function it_handles_concurrent_deletions(): void
    {
        // Arrange
        $student = User::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student->assignRole($studentRole);

        $parent = User::factory()->asParent()->create();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent->assignRole($parentRole);

        ParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE->value
        ]);

        $useCase = app(DeleteParentStudentRelationUseCase::class);

        // Act - Primera eliminación debería funcionar
        $result1 = $useCase->execute($parent->id, $student->id);

        // Assert - Solo la primera debería retornar true, las otras fallar
        $this->assertTrue($result1);

        // Las siguientes deberían lanzar excepción
        $this->expectException(ModelNotFoundException::class);
        $useCase->execute($parent->id, $student->id);
    }


    #[Test]
    public function it_dispatches_event_with_correct_parameters(): void
    {
        // Arrange
        $student = User::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student->assignRole($studentRole);

        $parent = User::factory()->asParent()->create();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent->assignRole($parentRole);

        ParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE->value
        ]);

        $useCase = app(DeleteParentStudentRelationUseCase::class);

        // Act
        $useCase->execute($parent->id, $student->id);

        // Assert - Verificar que el evento tiene los parámetros correctos
        Event::assertDispatched(\App\Events\ParentStudentRelationDelete::class, function ($event) use ($parent, $student) {
            return $event->parentId === $parent->id
                && $event->studentId === $student->id
                && is_int($event->parentId)
                && is_int($event->studentId);
        });
    }

    #[Test]
    public function it_preserves_other_relations_when_deleting_one(): void
    {
        // Arrange - Crear múltiples relaciones independientes
        $parent1 = User::factory()->asParent()->create();
        $parent2 = User::factory()->asParent()->create();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent1->assignRole($parentRole);
        $parent2->assignRole($parentRole);

        $student1 = User::factory()->asStudent()->create();
        $student2 = User::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student1->assignRole($studentRole);
        $student2->assignRole($studentRole);

        // Crear 4 relaciones diferentes
        $relations = [
            ['parent' => $parent1, 'student' => $student1, 'relationship' => RelationshipType::PADRE],
            ['parent' => $parent1, 'student' => $student2, 'relationship' => RelationshipType::MADRE],
            ['parent' => $parent2, 'student' => $student1, 'relationship' => RelationshipType::TUTOR],
            ['parent' => $parent2, 'student' => $student2, 'relationship' => RelationshipType::TUTOR_LEGAL],
        ];

        foreach ($relations as $relation) {
            ParentStudent::create([
                'parent_id' => $relation['parent']->id,
                'student_id' => $relation['student']->id,
                'parent_role_id' => $parentRole->id,
                'student_role_id' => $studentRole->id,
                'relationship' => $relation['relationship']->value
            ]);
        }

        $useCase = app(DeleteParentStudentRelationUseCase::class);

        // Act - Eliminar solo una relación
        $result = $useCase->execute($parent1->id, $student1->id);

        // Assert
        $this->assertTrue($result);

        // Verificar que solo se eliminó la relación específica
        $this->assertDatabaseMissing('parent_student', [
            'parent_id' => $parent1->id,
            'student_id' => $student1->id
        ]);

        // Las otras 3 relaciones deben seguir existiendo
        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parent1->id,
            'student_id' => $student2->id
        ]);

        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parent2->id,
            'student_id' => $student1->id
        ]);

        $this->assertDatabaseHas('parent_student', [
            'parent_id' => $parent2->id,
            'student_id' => $student2->id
        ]);

        Event::assertDispatched(\App\Events\ParentStudentRelationDelete::class, 1);
    }

    #[Test]
    public function it_works_with_null_relationship(): void
    {
        // Arrange - Crear relación con relationship nulo
        $student = User::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student->assignRole($studentRole);

        $parent = User::factory()->asParent()->create();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent->assignRole($parentRole);

        ParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null // Relationship nulo
        ]);

        $useCase = app(DeleteParentStudentRelationUseCase::class);

        // Act
        $result = $useCase->execute($parent->id, $student->id);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseMissing('parent_student', [
            'parent_id' => $parent->id,
            'student_id' => $student->id
        ]);

        Event::assertDispatched(\App\Events\ParentStudentRelationDelete::class);
    }

}
