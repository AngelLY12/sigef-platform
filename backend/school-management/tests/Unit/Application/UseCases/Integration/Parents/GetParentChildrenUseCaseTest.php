<?php

namespace Tests\Unit\Application\UseCases\Integration\Parents;

use App\Core\Application\DTO\Response\Parents\ParentChildrenResponse;
use App\Core\Application\UseCases\Parents\GetParentChildrenUseCase;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\User\RelationshipType;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Exceptions\NotAllowed\UserInvalidRoleException;
use App\Exceptions\NotFound\ParentChildrenNotFoundException;
use App\Models\ParentStudent;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use App\Models\User as UserModel;

class GetParentChildrenUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private GetParentChildrenUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCase = app(GetParentChildrenUseCase::class);
        $this->seed(RolesSeeder::class);
    }

    #[Test]
    public function it_returns_children_for_parent_with_single_child(): void
    {
        // Arrange
        // Crear usuario padre usando el factory
        $parent = UserModel::factory()->asParent()->create([
            'name' => 'John',
            'last_name' => 'Doe'
        ]);

        // Asignar rol de padre
        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        // Crear usuario estudiante usando el factory
        $student = UserModel::factory()->asStudent()->create([
            'name' => 'Jane',
            'last_name' => 'Doe'
        ]);

        // Asignar rol de estudiante
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        // Crear relación usando el factory
        ParentStudent::factory()->create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE
        ]);
        $parent->load('roles');

        // Crear entidad de dominio User
        $userEntity = UserMapper::toDomain($parent);
        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertInstanceOf(ParentChildrenResponse::class, $result);
        $this->assertEquals($parent->id, $result->parentId);
        $this->assertEquals('John Doe', $result->parentName);
        $this->assertCount(1, $result->childrenData);

        $child = $result->childrenData[0];
        $this->assertEquals($student->id, $child['id']);
        $this->assertEquals('Jane Doe', $child['fullName']);
    }

    #[Test]
    public function it_returns_children_for_parent_with_multiple_children(): void
    {
        // Arrange
        $parent = UserModel::factory()->asParent()->create([
            'name' => 'Maria',
            'last_name' => 'Garcia'
        ]);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        // Crear 3 estudiantes y relaciones usando el factory
        for ($i = 0; $i < 3; $i++) {
            $student = UserModel::factory()->asStudent()->create();
            $student->assignRole($studentRole);

            ParentStudent::factory()->create([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'parent_role_id' => $parentRole->id,
                'student_role_id' => $studentRole->id,
                'relationship' => RelationshipType::MADRE
            ]);
        }
        $parent->load('roles');

        $userEntity = UserMapper::toDomain($parent);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertInstanceOf(ParentChildrenResponse::class, $result);
        $this->assertEquals($parent->id, $result->parentId);
        $this->assertCount(3, $result->childrenData);

        // Verificar que todos los hijos están en la respuesta
        $childrenIds = array_column($result->childrenData, 'id');
        $this->assertCount(3, $childrenIds);
    }

    #[Test]
    public function it_throws_exception_when_user_is_not_parent(): void
    {
        // Arrange
        // Crear usuario con rol de estudiante, no padre
        $student = UserModel::factory()->asStudent()->create();

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);
        $student->load('roles');

        $userEntity = UserMapper::toDomain($student);
        // Assert
        $this->expectException(UserInvalidRoleException::class);

        // Act
        $this->useCase->execute($userEntity);
    }

    #[Test]
    public function it_throws_exception_when_parent_has_no_children(): void
    {
        // Arrange
        $parent = UserModel::factory()->asParent()->create();

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);
        $parent->load('roles');
        $userEntity = UserMapper::toDomain($parent);

        // Assert
        $this->expectException(ParentChildrenNotFoundException::class);

        // Act
        $this->useCase->execute($userEntity);
    }

    #[Test]
    public function it_returns_children_in_correct_format(): void
    {
        // Arrange
        $parent = UserModel::factory()->asParent()->create([
            'name' => 'Carlos',
            'last_name' => 'Rodriguez'
        ]);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        $student = UserModel::factory()->asStudent()->create([
            'name' => 'Ana',
            'last_name' => 'Rodriguez'
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        ParentStudent::factory()->create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE
        ]);
        $parent->load('roles');
        $userEntity = UserMapper::toDomain($parent);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertIsArray($result->childrenData);

        $childData = $result->childrenData[0];
        $this->assertArrayHasKey('id', $childData);
        $this->assertArrayHasKey('fullName', $childData);
        $this->assertEquals($student->id, $childData['id']);
        $this->assertEquals('Ana Rodriguez', $childData['fullName']);
    }

    #[Test]
    public function it_handles_parent_with_different_relationships(): void
    {
        // Arrange
        $parent = UserModel::factory()->asParent()->create();
        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        // Crear dos hijos con diferentes relaciones
        $child1 = UserModel::factory()->asStudent()->create();
        $child1->assignRole($studentRole);

        $child2 = UserModel::factory()->asStudent()->create();
        $child2->assignRole($studentRole);

        ParentStudent::factory()->create([
            'parent_id' => $parent->id,
            'student_id' => $child1->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE
        ]);

        ParentStudent::factory()->create([
            'parent_id' => $parent->id,
            'student_id' => $child2->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::TUTOR
        ]);
        $parent->load('roles');

        $userEntity = UserMapper::toDomain($parent);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertCount(2, $result->childrenData);
    }

    #[Test]
    public function it_returns_only_children_for_specific_parent(): void
    {
        // Arrange
        // Crear dos padres
        $parent1 = UserModel::factory()->asParent()->create(['name' => 'Parent1']);
        $parent2 = UserModel::factory()->asParent()->create(['name' => 'Parent2']);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent1->assignRole($parentRole);
        $parent2->assignRole($parentRole);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        // Crear hijos
        $child1 = UserModel::factory()->asStudent()->create(['name' => 'Child1']);
        $child1->assignRole($studentRole);

        $child2 = UserModel::factory()->asStudent()->create(['name' => 'Child2']);
        $child2->assignRole($studentRole);

        // Asignar hijos solo al primer padre
        ParentStudent::factory()->create([
            'parent_id' => $parent1->id,
            'student_id' => $child1->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE
        ]);

        ParentStudent::factory()->create([
            'parent_id' => $parent1->id,
            'student_id' => $child2->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE
        ]);
        $parent1->load('roles');
        $parent2->load('roles');
        $userEntity1 = UserMapper::toDomain($parent1);

        $userEntity2 = UserMapper::toDomain($parent2);

        // Act & Assert para parent1
        $result1 = $this->useCase->execute($userEntity1);
        $this->assertCount(2, $result1->childrenData);

        // Act & Assert para parent2 - debería lanzar excepción
        $this->expectException(ParentChildrenNotFoundException::class);
        $this->useCase->execute($userEntity2);
    }

    #[Test]
    public function it_handles_empty_names_correctly(): void
    {
        // Arrange
        $parent = UserModel::factory()->asParent()->create([
            'name' => '',
            'last_name' => ''
        ]);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        $student = UserModel::factory()->asStudent()->create([
            'name' => 'Child',
            'last_name' => 'Test'
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        ParentStudent::factory()->create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::PADRE
        ]);
        $parent->load('roles');

        $userEntity = UserMapper::toDomain($parent);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertEquals(' ', $result->parentName);
        $this->assertEquals('Child Test', $result->childrenData[0]['fullName']);
    }

    #[Test]
    public function it_returns_correct_data_with_mixed_case_names(): void
    {
        // Arrange
        $parent = UserModel::factory()->asParent()->create([
            'name' => 'jUAn',
            'last_name' => 'pEREZ'
        ]);

        $parentRole = Role::where('name', UserRoles::PARENT->value)->firstOrFail();
        $parent->assignRole($parentRole);

        $student = UserModel::factory()->asStudent()->create([
            'name' => 'MARIA',
            'last_name' => 'lopez'
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $student->assignRole($studentRole);

        ParentStudent::factory()->create([
            'parent_id' => $parent->id,
            'student_id' => $student->id,
            'parent_role_id' => $parentRole->id,
            'student_role_id' => $studentRole->id,
            'relationship' => RelationshipType::MADRE
        ]);
        $parent->load('roles');

        $userEntity = UserMapper::toDomain($parent);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertEquals('jUAn pEREZ', $result->parentName);
        $this->assertEquals('MARIA lopez', $result->childrenData[0]['fullName']);
    }

}
