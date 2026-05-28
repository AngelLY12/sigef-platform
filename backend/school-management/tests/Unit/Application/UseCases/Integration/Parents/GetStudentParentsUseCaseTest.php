<?php

namespace Tests\Unit\Application\UseCases\Integration\Parents;

use App\Core\Application\DTO\Response\Parents\StudentParentsResponse;
use App\Core\Application\UseCases\Parents\GetStudentParentsUseCase;
use App\Core\Domain\Enum\User\RelationshipType;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Exceptions\NotAllowed\UserInvalidRoleException;
use App\Exceptions\NotFound\StudentParentsNotFoundException;
use App\Models\ParentStudent;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use App\Models\User as UserModel;

class GetStudentParentsUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private GetStudentParentsUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCase = app(GetStudentParentsUseCase::class);
        $this->seed(RolesSeeder::class);
    }

    private function createUserEntityWithRoles(UserModel $user): \App\Core\Domain\Entities\User
    {
        // Asegurar que las relaciones estén cargadas
        if (!$user->relationLoaded('roles')) {
            $user->load('roles');
        }

        return UserMapper::toDomain($user);
    }

    #[Test]
    public function it_returns_parents_for_student_with_single_parent(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create([
            'name' => 'Juan',
            'last_name' => 'Perez'
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $parent = UserModel::factory()->asParent()->create([
            'name' => 'Carlos',
            'last_name' => 'Perez'
        ]);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        ParentStudent::factory()->create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE
        ]);

        // Cargar relaciones
        $student->load('roles');
        $userEntity = UserMapper::toDomain($student);

        // Verificar que tiene el rol de estudiante
        $this->assertTrue($userEntity->isStudent());

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertInstanceOf(StudentParentsResponse::class, $result);
        $this->assertEquals($student->id, $result->studentId);
        $this->assertEquals('Juan Perez', $result->studentName);
        $this->assertCount(1, $result->parentsData);

        $parentData = $result->parentsData[0];
        $this->assertEquals($parent->id, $parentData['id']);
        $this->assertEquals('Carlos Perez', $parentData['fullName']);
    }

    #[Test]
    public function it_returns_parents_for_student_with_multiple_parents(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create([
            'name' => 'Maria',
            'last_name' => 'Gonzalez'
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();

        // Crear 3 padres con diferentes relaciones
        $father = UserModel::factory()->asParent()->create([
            'name' => 'Roberto',
            'last_name' => 'Gonzalez'
        ]);
        $father->assignRole($parentRole);

        $mother = UserModel::factory()->asParent()->create([
            'name' => 'Laura',
            'last_name' => 'Gonzalez'
        ]);
        $mother->assignRole($parentRole);

        $tutor = UserModel::factory()->asParent()->create([
            'name' => 'Miguel',
            'last_name' => 'Ramirez'
        ]);
        $tutor->assignRole($parentRole);

        // Crear relaciones con diferentes tipos
        ParentStudent::factory()->create([
            'parent_id' => $father->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE
        ]);

        ParentStudent::factory()->create([
            'parent_id' => $mother->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::MADRE
        ]);

        ParentStudent::factory()->create([
            'parent_id' => $tutor->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::TUTOR
        ]);

        // Cargar relaciones
        $student->load('roles');
        $userEntity = UserMapper::toDomain($student);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertInstanceOf(StudentParentsResponse::class, $result);
        $this->assertEquals($student->id, $result->studentId);
        $this->assertEquals('Maria Gonzalez', $result->studentName);
        $this->assertCount(3, $result->parentsData);

        // Verificar que todos los padres están en la respuesta
        $parentIds = array_column($result->parentsData, 'id');
        $this->assertContains($father->id, $parentIds);
        $this->assertContains($mother->id, $parentIds);
        $this->assertContains($tutor->id, $parentIds);
    }

    #[Test]
    public function it_throws_exception_when_user_is_not_student(): void
    {
        // Arrange
        $parent = UserModel::factory()->asParent()->create();

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        // Cargar relaciones
        $parent->load('roles');
        $userEntity = UserMapper::toDomain($parent);

        // Verificar que NO es estudiante
        $this->assertFalse($userEntity->isStudent());

        // Assert
        $this->expectException(UserInvalidRoleException::class);
        $this->expectExceptionMessage('El usuario no tiene el rol necesario.');

        // Act
        $this->useCase->execute($userEntity);
    }

    #[Test]
    public function it_throws_exception_when_student_has_no_parents(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create();

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        // Cargar relaciones
        $student->load('roles');
        $userEntity = UserMapper::toDomain($student);

        // Verificar que es estudiante
        $this->assertTrue($userEntity->isStudent());

        // Assert
        $this->expectException(StudentParentsNotFoundException::class);
        $this->expectExceptionMessage('No se encontraron parientes relacionados a este usuario');

        // Act
        $this->useCase->execute($userEntity);
    }

    #[Test]
    public function it_returns_parents_in_correct_format(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create([
            'name' => 'Ana',
            'last_name' => 'Martinez'
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $parent = UserModel::factory()->asParent()->create([
            'name' => 'Jorge',
            'last_name' => 'Martinez'
        ]);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        ParentStudent::factory()->create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE
        ]);

        // Cargar relaciones
        $student->load('roles');
        $userEntity = UserMapper::toDomain($student);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertIsArray($result->parentsData);

        $parentData = $result->parentsData[0];
        $this->assertArrayHasKey('id', $parentData);
        $this->assertArrayHasKey('fullName', $parentData);
        $this->assertEquals($parent->id, $parentData['id']);
        $this->assertEquals('Jorge Martinez', $parentData['fullName']);
    }

    #[Test]
    public function it_handles_student_with_different_parent_relationships(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create();

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();

        // Crear padres con diferentes relaciones
        $father = UserModel::factory()->asParent()->create();
        $father->assignRole($parentRole);

        $mother = UserModel::factory()->asParent()->create();
        $mother->assignRole($parentRole);

        $legalGuardian = UserModel::factory()->asParent()->create();
        $legalGuardian->assignRole($parentRole);

        ParentStudent::factory()->create([
            'parent_id' => $father->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE
        ]);

        ParentStudent::factory()->create([
            'parent_id' => $mother->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::MADRE
        ]);

        ParentStudent::factory()->create([
            'parent_id' => $legalGuardian->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::TUTOR_LEGAL
        ]);

        // Cargar relaciones
        $student->load('roles');
        $userEntity = UserMapper::toDomain($student);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertCount(3, $result->parentsData);
    }

    #[Test]
    public function it_returns_only_parents_for_specific_student(): void
    {
        // Arrange
        // Crear dos estudiantes
        $student1 = UserModel::factory()->asStudent()->create(['name' => 'Student1']);
        $student2 = UserModel::factory()->asStudent()->create(['name' => 'Student2']);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student1->assignRole($studentRole);
        $student2->assignRole($studentRole);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();

        // Crear padres
        $parent1 = UserModel::factory()->asParent()->create(['name' => 'Parent1']);
        $parent1->assignRole($parentRole);

        $parent2 = UserModel::factory()->asParent()->create(['name' => 'Parent2']);
        $parent2->assignRole($parentRole);

        // Asignar padres solo al primer estudiante
        ParentStudent::factory()->create([
            'parent_id' => $parent1->id,
            'student_id' => $student1->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE
        ]);

        ParentStudent::factory()->create([
            'parent_id' => $parent2->id,
            'student_id' => $student1->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::MADRE
        ]);

        // Cargar relaciones para ambos estudiantes
        $student1->load('roles');
        $student2->load('roles');

        $userEntity1 = UserMapper::toDomain($student1);
        $userEntity2 = UserMapper::toDomain($student2);

        // Act & Assert para student1
        $result1 = $this->useCase->execute($userEntity1);
        $this->assertCount(2, $result1->parentsData);

        // Act & Assert para student2 - debería lanzar excepción
        $this->expectException(StudentParentsNotFoundException::class);
        $this->useCase->execute($userEntity2);
    }

    #[Test]
    public function it_handles_empty_names_correctly(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create([
            'name' => '',
            'last_name' => ''
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $parent = UserModel::factory()->asParent()->create([
            'name' => 'Parent',
            'last_name' => 'Test'
        ]);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        ParentStudent::factory()->create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE
        ]);

        // Cargar relaciones
        $student->load('roles');
        $userEntity = UserMapper::toDomain($student);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertEquals(' ', $result->studentName);
        $this->assertEquals('Parent Test', $result->parentsData[0]['fullName']);
    }

    #[Test]
    public function it_returns_correct_data_with_mixed_case_names(): void
    {
        // Arrange
        $student = UserModel::factory()->asStudent()->create([
            'name' => 'jUAn',
            'last_name' => 'pEREZ'
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        $parent = UserModel::factory()->asParent()->create([
            'name' => 'CARLOS',
            'last_name' => 'gomez'
        ]);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        ParentStudent::factory()->create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE
        ]);

        // Cargar relaciones
        $student->load('roles');
        $userEntity = UserMapper::toDomain($student);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertEquals('jUAn pEREZ', $result->studentName);
        $this->assertEquals('CARLOS gomez', $result->parentsData[0]['fullName']);
    }

    #[Test]
    public function it_works_with_parent_student_factory_directly(): void
    {
        // Arrange - Usar ParentStudentFactory que ya crea todo
        $relation = ParentStudent::factory()->create();

        $student = $relation->student;

        // Cargar relaciones
        $student->load('roles');
        $userEntity = UserMapper::toDomain($student);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertInstanceOf(StudentParentsResponse::class, $result);
        $this->assertCount(1, $result->parentsData);
        $this->assertEquals($relation->parent->id, $result->parentsData[0]['id']);
    }

}
