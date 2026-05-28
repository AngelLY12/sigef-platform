<?php

namespace Tests\Unit\Infraestructure\Repositories\Query;

use App\Core\Application\DTO\Response\Parents\ParentChildrenResponse;
use App\Core\Application\DTO\Response\Parents\StudentParentsResponse;
use App\Core\Domain\Enum\User\UserRoles;
use App\Models\ParentStudent as EloquentParentStudent;
use App\Core\Infraestructure\Repositories\Query\User\EloquentParentStudentQueryRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EloquentParentStudentQueryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentParentStudentQueryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles necesarios antes de cada test
        Role::create(['name' => UserRoles::PARENT->value, 'guard_name' => 'web']);
        Role::create(['name' => UserRoles::STUDENT->value, 'guard_name' => 'web']);

        $this->repository = new EloquentParentStudentQueryRepository();
    }

    // ==================== GET STUDENTS OF PARENT TESTS ====================

    #[Test]
    public function get_students_of_parent_with_multiple_students(): void
    {
        // Arrange
        // Crear un padre
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent = User::factory()->create();
        $parent->assignRole($parentRole);

        // Crear varios estudiantes
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student1 = User::factory()->create(['name' => 'Juan', 'last_name' => 'Pérez']);
        $student1->assignRole($studentRole);

        $student2 = User::factory()->create(['name' => 'María', 'last_name' => 'González']);
        $student2->assignRole($studentRole);

        $student3 = User::factory()->create(['name' => 'Carlos', 'last_name' => 'López']);
        $student3->assignRole($studentRole);

        // Crear relaciones padre-estudiante
        EloquentParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $student1->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        EloquentParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $student2->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        EloquentParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $student3->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        // Act
        $result = $this->repository->getStudentsOfParent($parent->id);

        // Assert
        $this->assertInstanceOf(ParentChildrenResponse::class, $result);
        $this->assertEquals($parent->id, $result->parentId);
        $this->assertEquals("{$parent->name} {$parent->last_name}", $result->parentName);
        $this->assertCount(3, $result->childrenData);

        // Verificar que todos los estudiantes están presentes
        $studentNames = array_map(fn($child) => $child['fullName'], $result->childrenData);

        $this->assertContains('Juan Pérez', $studentNames);
        $this->assertContains('María González', $studentNames);
        $this->assertContains('Carlos López', $studentNames);

        // Verificar IDs
        $studentIds = array_map(fn($child) => $child['id'], $result->childrenData);
        $this->assertContains($student1->id, $studentIds);
        $this->assertContains($student2->id, $studentIds);
        $this->assertContains($student3->id, $studentIds);
    }

    #[Test]
    public function get_students_of_parent_with_single_student(): void
    {
        // Arrange
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent = User::factory()->create(['name' => 'Ana', 'last_name' => 'Martínez']);
        $parent->assignRole($parentRole);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student = User::factory()->create(['name' => 'Luis', 'last_name' => 'Martínez']);
        $student->assignRole($studentRole);

        EloquentParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        // Act
        $result = $this->repository->getStudentsOfParent($parent->id);

        // Assert
        $this->assertInstanceOf(ParentChildrenResponse::class, $result);
        $this->assertEquals($parent->id, $result->parentId);
        $this->assertEquals('Ana Martínez', $result->parentName);
        $this->assertCount(1, $result->childrenData);
        $this->assertEquals($student->id, $result->childrenData[0]['id']);
        $this->assertEquals('Luis Martínez', $result->childrenData[0]['fullName']);
    }

    #[Test]
    public function get_students_of_parent_returns_null_for_parent_without_students(): void
    {
        // Arrange
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $parent = User::factory()->create();
        $parent->assignRole($parentRole);

        // Act
        $result = $this->repository->getStudentsOfParent($parent->id);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function get_students_of_parent_returns_null_for_nonexistent_parent(): void
    {
        // Act
        $result = $this->repository->getStudentsOfParent(999999);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function get_students_of_parent_with_parent_that_is_not_parent_role(): void
    {
        // Arrange - Usuario sin rol de padre
        $user = User::factory()->create();
        // No asignar rol de padre

        // Act
        $result = $this->repository->getStudentsOfParent($user->id);

        // Assert - Debería retornar null porque no tiene relaciones
        $this->assertNull($result);
    }

    #[Test]
    public function get_students_of_parent_with_empty_relations(): void
    {
        // Arrange
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();

        $parent = User::factory()->create();
        $parent->assignRole($parentRole);

        $student = User::factory()->create();
        $student->assignRole($studentRole);

        // Crear relación pero luego eliminar (para forzar empty)
        $relation = EloquentParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        $relation->delete();

        // Act
        $result = $this->repository->getStudentsOfParent($parent->id);

        // Assert
        $this->assertNull($result);
    }

    // ==================== GET PARENTS OF STUDENT TESTS ====================

    #[Test]
    public function get_parents_of_student_with_multiple_parents(): void
    {
        // Arrange
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();

        // Crear estudiante
        $student = User::factory()->create(['name' => 'Laura', 'last_name' => 'Sánchez']);
        $student->assignRole($studentRole);

        // Crear varios padres
        $parent1 = User::factory()->create(['name' => 'Pedro', 'last_name' => 'Sánchez']);
        $parent1->assignRole($parentRole);

        $parent2 = User::factory()->create(['name' => 'María', 'last_name' => 'Sánchez']);
        $parent2->assignRole($parentRole);

        $parent3 = User::factory()->create(['name' => 'Roberto', 'last_name' => 'Gómez']);
        $parent3->assignRole($parentRole);

        // Crear relaciones
        EloquentParentStudent::create([
            'parent_id' => $parent1->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        EloquentParentStudent::create([
            'parent_id' => $parent2->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        EloquentParentStudent::create([
            'parent_id' => $parent3->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        // Act
        $result = $this->repository->getParentsOfStudent($student->id);

        // Assert
        $this->assertInstanceOf(StudentParentsResponse::class, $result);
        $this->assertEquals($student->id, $result->studentId);
        $this->assertEquals('Laura Sánchez', $result->studentName);
        $this->assertCount(3, $result->parentsData);

        // Verificar que todos los padres están presentes
        $parentNames = array_map(fn($parent) => $parent['fullName'], $result->parentsData);

        $this->assertContains('Pedro Sánchez', $parentNames);
        $this->assertContains('María Sánchez', $parentNames);
        $this->assertContains('Roberto Gómez', $parentNames);

        // Verificar IDs
        $parentIds = array_map(fn($parent) => $parent['id'], $result->parentsData);
        $this->assertContains($parent1->id, $parentIds);
        $this->assertContains($parent2->id, $parentIds);
        $this->assertContains($parent3->id, $parentIds);
    }

    #[Test]
    public function get_parents_of_student_with_single_parent(): void
    {
        // Arrange
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();

        $parent = User::factory()->create(['name' => 'José', 'last_name' => 'Ramírez']);
        $parent->assignRole($parentRole);

        $student = User::factory()->create(['name' => 'Sofía', 'last_name' => 'Ramírez']);
        $student->assignRole($studentRole);

        EloquentParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        // Act
        $result = $this->repository->getParentsOfStudent($student->id);

        // Assert
        $this->assertInstanceOf(StudentParentsResponse::class, $result);
        $this->assertEquals($student->id, $result->studentId);
        $this->assertEquals('Sofía Ramírez', $result->studentName);
        $this->assertCount(1, $result->parentsData);
        $this->assertEquals($parent->id, $result->parentsData[0]['id']);
        $this->assertEquals('José Ramírez', $result->parentsData[0]['fullName']);
    }

    #[Test]
    public function get_parents_of_student_returns_null_for_student_without_parents(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();
        $student = User::factory()->create();
        $student->assignRole($studentRole);

        // Act
        $result = $this->repository->getParentsOfStudent($student->id);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function get_parents_of_student_returns_null_for_nonexistent_student(): void
    {
        // Act
        $result = $this->repository->getParentsOfStudent(999999);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function get_parents_of_student_with_student_that_is_not_student_role(): void
    {
        // Arrange - Usuario sin rol de estudiante
        $user = User::factory()->create();
        // No asignar rol de estudiante

        // Act
        $result = $this->repository->getParentsOfStudent($user->id);

        // Assert - Debería retornar null porque no tiene relaciones
        $this->assertNull($result);
    }

    #[Test]
    public function get_parents_of_student_handles_duplicate_parents_correctly(): void
    {
        // Arrange
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();

        $student = User::factory()->create([
            'id' => 1001,
            'name' => 'Laura',
            'last_name' => 'Sánchez'
        ]);
        $student->assignRole($studentRole);

        // Crear varios padres con IDs específicos
        $parent1 = User::factory()->create([
            'id' => 2001,
            'name' => 'Pedro',
            'last_name' => 'Sánchez'
        ]);
        $parent1->assignRole($parentRole);

        $parent2 = User::factory()->create([
            'id' => 2002,
            'name' => 'María',
            'last_name' => 'Sánchez'
        ]);
        $parent2->assignRole($parentRole);

        $parent3 = User::factory()->create([
            'id' => 2003,
            'name' => 'Roberto',
            'last_name' => 'Gómez'
        ]);
        $parent3->assignRole($parentRole);

        // Verificar que no existan relaciones antes de crearlas
        if (!EloquentParentStudent::where('parent_id', $parent1->id)->where('student_id', $student->id)->exists()) {
            EloquentParentStudent::create([
                'parent_id' => $parent1->id,
                'student_id' => $student->id,
                'parent_role_id' => $parentRole->id,
                'student_role_id' => $studentRole->id,
                'relationship' => null
            ]);
        }

        if (!EloquentParentStudent::where('parent_id', $parent2->id)->where('student_id', $student->id)->exists()) {
            EloquentParentStudent::create([
                'parent_id' => $parent2->id,
                'student_id' => $student->id,
                'parent_role_id' => $parentRole->id,
                'student_role_id' => $studentRole->id,
                'relationship' => null
            ]);
        }

        if (!EloquentParentStudent::where('parent_id', $parent3->id)->where('student_id', $student->id)->exists()) {
            EloquentParentStudent::create([
                'parent_id' => $parent3->id,
                'student_id' => $student->id,
                'parent_role_id' => $parentRole->id,
                'student_role_id' => $studentRole->id,
                'relationship' => null
            ]);
        }

        // Act
        $result = $this->repository->getParentsOfStudent($student->id);

        // Assert
        $this->assertInstanceOf(StudentParentsResponse::class, $result);
        $this->assertEquals($student->id, $result->studentId);
        $this->assertEquals('Laura Sánchez', $result->studentName);
        $this->assertCount(3, $result->parentsData);
    }

    // ==================== EXISTS TESTS ====================

    #[Test]
    public function exists_returns_true_for_valid_relation(): void
    {
        // Arrange
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();

        $parent = User::factory()->create();
        $parent->assignRole($parentRole);

        $student = User::factory()->create();
        $student->assignRole($studentRole);

        EloquentParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        // Act
        $result = $this->repository->exists($parent->id, $student->id);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function exists_returns_false_for_nonexistent_relation(): void
    {
        // Arrange
        $parent = User::factory()->create();
        $student = User::factory()->create();

        // Act
        $result = $this->repository->exists($parent->id, $student->id);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function exists_returns_false_for_deleted_relation(): void
    {
        // Arrange
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();

        $parent = User::factory()->create();
        $parent->assignRole($parentRole);

        $student = User::factory()->create();
        $student->assignRole($studentRole);

        $relation = EloquentParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        // Eliminar la relación
        $relation->delete();

        // Act
        $result = $this->repository->exists($parent->id, $student->id);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function exists_returns_false_for_wrong_parent_id(): void
    {
        // Arrange
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();

        $realParent = User::factory()->create();
        $realParent->assignRole($parentRole);

        $wrongParent = User::factory()->create();
        $wrongParent->assignRole($parentRole);

        $student = User::factory()->create();
        $student->assignRole($studentRole);

        EloquentParentStudent::create([
            'parent_id' => $realParent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        // Act - Usar ID de padre incorrecto
        $result = $this->repository->exists($wrongParent->id, $student->id);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function exists_returns_false_for_wrong_student_id(): void
    {
        // Arrange
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();

        $parent = User::factory()->create();
        $parent->assignRole($parentRole);

        $realStudent = User::factory()->create();
        $realStudent->assignRole($studentRole);

        $wrongStudent = User::factory()->create();
        $wrongStudent->assignRole($studentRole);

        EloquentParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $realStudent->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        // Act - Usar ID de estudiante incorrecto
        $result = $this->repository->exists($parent->id, $wrongStudent->id);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function exists_returns_true_for_multiple_relations(): void
    {
        // Arrange
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();

        $parent = User::factory()->create();
        $parent->assignRole($parentRole);

        // Crear varios estudiantes para el mismo padre
        $student1 = User::factory()->create();
        $student1->assignRole($studentRole);

        $student2 = User::factory()->create();
        $student2->assignRole($studentRole);

        $student3 = User::factory()->create();
        $student3->assignRole($studentRole);

        EloquentParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $student1->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        EloquentParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $student2->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        // Act - Verificar solo una de las relaciones
        $result = $this->repository->exists($parent->id, $student1->id);

        // Assert
        $this->assertTrue($result);
    }

    // ==================== INTEGRATION TESTS ====================

    #[Test]
    public function complete_parent_student_scenario(): void
    {
        // 1. Crear roles
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();

        // 2. Crear familia: 2 padres, 3 estudiantes
        $father = User::factory()->create(['name' => 'Juan', 'last_name' => 'García']);
        $father->assignRole($parentRole);

        $mother = User::factory()->create(['name' => 'Ana', 'last_name' => 'García']);
        $mother->assignRole($parentRole);

        $student1 = User::factory()->create(['name' => 'Pedro', 'last_name' => 'García']);
        $student1->assignRole($studentRole);

        $student2 = User::factory()->create(['name' => 'Lucía', 'last_name' => 'García']);
        $student2->assignRole($studentRole);

        $student3 = User::factory()->create(['name' => 'Carlos', 'last_name' => 'García']);
        $student3->assignRole($studentRole);

        // 3. Crear relaciones padre-hijo
        // Padre con todos los hijos
        EloquentParentStudent::create([
            'parent_id' => $father->id,
            'student_id' => $student1->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        EloquentParentStudent::create([
            'parent_id' => $father->id,
            'student_id' => $student2->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        EloquentParentStudent::create([
            'parent_id' => $father->id,
            'student_id' => $student3->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        // Madre solo con los primeros 2 hijos
        EloquentParentStudent::create([
            'parent_id' => $mother->id,
            'student_id' => $student1->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        EloquentParentStudent::create([
            'parent_id' => $mother->id,
            'student_id' => $student2->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        // 4. Probar getStudentsOfParent para el padre
        $fatherStudents = $this->repository->getStudentsOfParent($father->id);
        $this->assertInstanceOf(ParentChildrenResponse::class, $fatherStudents);
        $this->assertEquals('Juan García', $fatherStudents->parentName);
        $this->assertCount(3, $fatherStudents->childrenData);

        // 5. Probar getStudentsOfParent para la madre
        $motherStudents = $this->repository->getStudentsOfParent($mother->id);
        $this->assertInstanceOf(ParentChildrenResponse::class, $motherStudents);
        $this->assertEquals('Ana García', $motherStudents->parentName);
        $this->assertCount(2, $motherStudents->childrenData);

        // 6. Probar getParentsOfStudent para estudiante 1 (tiene ambos padres)
        $student1Parents = $this->repository->getParentsOfStudent($student1->id);
        $this->assertInstanceOf(StudentParentsResponse::class, $student1Parents);
        $this->assertEquals('Pedro García', $student1Parents->studentName);
        $this->assertCount(2, $student1Parents->parentsData);

        // 7. Probar getParentsOfStudent para estudiante 3 (solo tiene padre)
        $student3Parents = $this->repository->getParentsOfStudent($student3->id);
        $this->assertInstanceOf(StudentParentsResponse::class, $student3Parents);
        $this->assertEquals('Carlos García', $student3Parents->studentName);
        $this->assertCount(1, $student3Parents->parentsData);

        // 8. Probar exists para relaciones existentes
        $this->assertTrue($this->repository->exists($father->id, $student1->id));
        $this->assertTrue($this->repository->exists($mother->id, $student1->id));
        $this->assertTrue($this->repository->exists($father->id, $student3->id));

        // 9. Probar exists para relaciones inexistentes
        $this->assertFalse($this->repository->exists($mother->id, $student3->id)); // Madre no tiene a Carlos
        $this->assertFalse($this->repository->exists($father->id, 999999)); // Estudiante inexistente
        $this->assertFalse($this->repository->exists(999999, $student1->id)); // Padre inexistente

        // 10. Probar casos límite
        $nonexistent = $this->repository->getStudentsOfParent(999999);
        $this->assertNull($nonexistent);

        $nonexistentStudent = $this->repository->getParentsOfStudent(999999);
        $this->assertNull($nonexistentStudent);
    }

    #[Test]
    public function repository_methods_independent_of_database_state(): void
    {
        // Este test verifica que cada método funciona independientemente

        // 1. Preparar datos iniciales
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();

        $parent1 = User::factory()->create(['name' => 'Parent1']);
        $parent1->assignRole($parentRole);

        $parent2 = User::factory()->create(['name' => 'Parent2']);
        $parent2->assignRole($parentRole);

        $student1 = User::factory()->create(['name' => 'Student1']);
        $student1->assignRole($studentRole);

        $student2 = User::factory()->create(['name' => 'Student2']);
        $student2->assignRole($studentRole);

        // 2. Probar exists sin relaciones (debe ser false)
        $existsBefore = $this->repository->exists($parent1->id, $student1->id);
        $this->assertFalse($existsBefore);

        // 3. Crear relación y probar exists
        EloquentParentStudent::create([
            'parent_id' => $parent1->id,
            'student_id' => $student1->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        $existsAfter = $this->repository->exists($parent1->id, $student1->id);
        $this->assertTrue($existsAfter);

        // 4. Probar getStudentsOfParent
        $students = $this->repository->getStudentsOfParent($parent1->id);
        $this->assertInstanceOf(ParentChildrenResponse::class, $students);
        $this->assertCount(1, $students->childrenData);

        // 5. Probar getParentsOfStudent
        $parents = $this->repository->getParentsOfStudent($student1->id);
        $this->assertInstanceOf(StudentParentsResponse::class, $parents);
        $this->assertCount(1, $parents->parentsData);

        // 6. Agregar más relaciones
        EloquentParentStudent::create([
            'parent_id' => $parent1->id,
            'student_id' => $student2->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        EloquentParentStudent::create([
            'parent_id' => $parent2->id,
            'student_id' => $student1->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        // 7. Verificar que los métodos reflejan los cambios
        $updatedStudents = $this->repository->getStudentsOfParent($parent1->id);
        $this->assertCount(2, $updatedStudents->childrenData);

        $updatedParents = $this->repository->getParentsOfStudent($student1->id);
        $this->assertCount(2, $updatedParents->parentsData);
    }

    // ==================== EDGE CASES TESTS ====================

    #[Test]
    public function handles_special_characters_in_names(): void
    {
        // Arrange
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();

        $parent = User::factory()->create([
            'name' => 'José María',
            'last_name' => 'González-López'
        ]);
        $parent->assignRole($parentRole);

        $student = User::factory()->create([
            'name' => 'Ana Sofía',
            'last_name' => 'García-Márquez'
        ]);
        $student->assignRole($studentRole);

        EloquentParentStudent::create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => null
        ]);

        // Act
        $studentsResult = $this->repository->getStudentsOfParent($parent->id);
        $parentsResult = $this->repository->getParentsOfStudent($student->id);

        // Assert
        $this->assertEquals('José María González-López', $studentsResult->parentName);
        $this->assertEquals('Ana Sofía García-Márquez', $parentsResult->studentName);
    }

    #[Test]
    public function performance_with_multiple_relations(): void
    {
        // Arrange - Crear muchas relaciones
        $parentRole = Role::where('name', UserRoles::PARENT->value)->first();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->first();

        $parent = User::factory()->create();
        $parent->assignRole($parentRole);

        // Crear 50 estudiantes
        $studentIds = [];
        for ($i = 0; $i < 50; $i++) {
            $student = User::factory()->create();
            $student->assignRole($studentRole);
            $studentIds[] = $student->id;

            EloquentParentStudent::create([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'parent_role_id' => $parentRole->id,
                'student_role_id' => $studentRole->id,
                'relationship' => null
            ]);
        }

        // Act
        $result = $this->repository->getStudentsOfParent($parent->id);

        // Assert
        $this->assertInstanceOf(ParentChildrenResponse::class, $result);
        $this->assertCount(50, $result->childrenData);

        // Verificar que existe para un estudiante aleatorio
        $randomStudentId = $studentIds[array_rand($studentIds)];
        $this->assertTrue($this->repository->exists($parent->id, $randomStudentId));
    }

}
