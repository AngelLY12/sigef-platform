<?php

namespace Tests\Unit\Infraestructure\Mappers;

use App\Core\Domain\Enum\User\RelationshipType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\ParentStudent;
use App\Core\Domain\Entities\ParentStudent as DomainParentStudent;
use App\Core\Infraestructure\Mappers\ParentStudentMapper;


class ParentStudentMapperTest extends TestCase
{
    #[Test]
    public function it_maps_from_eloquent_to_domain_correctly(): void
    {
        // Arrange
        $eloquentModel = new ParentStudent([
            'parent_id' => 10,
            'student_id' => 20,
            'parent_role_id' => 30,
            'student_role_id' => 40,
            'relationship' => RelationshipType::PADRE,
        ]);

        // Act
        $domainEntity = ParentStudentMapper::toDomain($eloquentModel);

        // Assert
        $this->assertInstanceOf(DomainParentStudent::class, $domainEntity);
        $this->assertEquals(10, $domainEntity->parentId);
        $this->assertEquals(20, $domainEntity->studentId);
        $this->assertEquals(30, $domainEntity->parentRoleId);
        $this->assertEquals(40, $domainEntity->studentRoleId);
        $this->assertEquals(RelationshipType::PADRE, $domainEntity->relationship);
    }

    #[Test]
    public function it_maps_from_domain_to_persistence_array_correctly(): void
    {
        // Arrange
        $domainEntity = new DomainParentStudent(
            parentId: 10,
            studentId: 20,
            parentRoleId: 30,
            studentRoleId: 40,
            relationship: RelationshipType::MADRE
        );

        // Act
        $persistenceArray = ParentStudentMapper::toPersistence($domainEntity);

        // Assert
        $this->assertIsArray($persistenceArray);
        $this->assertEquals([
            'parent_id' => 10,
            'student_id' => 20,
            'parent_role_id' => 30,
            'student_role_id' => 40,
            'relationship' => RelationshipType::MADRE,
        ], $persistenceArray);
    }

    #[Test]
    public function it_handles_empty_relationship_field(): void
    {
        // Arrange
        $eloquentModel = new ParentStudent([
            'parent_id' => 10,
            'student_id' => 20,
            'parent_role_id' => 30,
            'student_role_id' => 40,
            'relationship' => null,
        ]);

        // Act
        $domainEntity = ParentStudentMapper::toDomain($eloquentModel);

        // Assert
        $this->assertNull($domainEntity->relationship);
    }

    #[Test]
    public function it_preserves_all_data_in_bidirectional_mapping(): void
    {
        // Arrange
        $originalData = [
            'parent_id' => 15,
            'student_id' => 25,
            'parent_role_id' => 35,
            'student_role_id' => 45,
            'relationship' => RelationshipType::TUTOR,
        ];

        $eloquentModel = new ParentStudent($originalData);

        // Act
        $domainEntity = ParentStudentMapper::toDomain($eloquentModel);
        $persistenceArray = ParentStudentMapper::toPersistence($domainEntity);

        // Assert
        $this->assertEquals($originalData, $persistenceArray);
    }

}
