<?php

namespace Tests\Unit\Application\Mappers;

use App\Core\Application\DTO\Response\Parents\ParentChildrenResponse;
use App\Core\Application\DTO\Response\Parents\StudentParentsResponse;
use App\Core\Application\Mappers\ParentStudentMapper;
use App\Core\Domain\Entities\ParentStudent;
use App\Core\Domain\Enum\User\RelationshipType;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ParentStudentMapperTest extends TestCase
{
    // ==================== TO DOMAIN TESTS ====================

    #[Test]
    public function to_domain_creates_parent_student_with_correct_data(): void
    {
        // Arrange
        $data = [
            'parentId' => 100,
            'studentId' => 200,
            'parentRoleId' => 10,
            'studentRoleId' => 20,
            'relationship' => 'padre',
        ];

        // Act
        $result = ParentStudentMapper::toDomain($data);

        // Assert
        $this->assertInstanceOf(ParentStudent::class, $result);
        $this->assertEquals(100, $result->parentId);
        $this->assertEquals(200, $result->studentId);
        $this->assertEquals(10, $result->parentRoleId);
        $this->assertEquals(20, $result->studentRoleId);
        $this->assertEquals(RelationshipType::PADRE, $result->relationship);
    }

    #[Test]
    public function to_domain_with_null_relationship(): void
    {
        // Arrange
        $data = [
            'parentId' => 100,
            'studentId' => 200,
            'parentRoleId' => 10,
            'studentRoleId' => 20,
            // 'relationship' no está definido
        ];

        // Act
        $result = ParentStudentMapper::toDomain($data);

        // Assert
        $this->assertInstanceOf(ParentStudent::class, $result);
        $this->assertEquals(100, $result->parentId);
        $this->assertEquals(200, $result->studentId);
        $this->assertEquals(10, $result->parentRoleId);
        $this->assertEquals(20, $result->studentRoleId);
        $this->assertNull($result->relationship);
    }


    #[Test]
    public function to_domain_maps_all_relationship_types(): void
    {
        $relationshipTypes = [
            'PADRE' => RelationshipType::PADRE,
            'MADRE' => RelationshipType::MADRE,
            'TUTOR' => RelationshipType::TUTOR,
            'TUTOR_LEGAL' => RelationshipType::TUTOR_LEGAL,
        ];

        foreach ($relationshipTypes as $relationshipType => $enumType) {
            $data = [
                'parentId' => 100,
                'studentId' => 200,
                'parentRoleId' => 10,
                'studentRoleId' => 20,
                'relationship' => $relationshipType,
            ];

            $result = ParentStudentMapper::toDomain($data);
            $this->assertEquals($enumType, $result->relationship,
                "Failed to map relationship: {$relationshipType}");
        }
    }

    #[Test]
    public function to_domain_throws_exception_for_invalid_relationship(): void
    {
        // Arrange
        $invalidRelationships = [
            'INVALID',
            'father', // lowercase
            'FATHER ', // with space
            'PARENT',
            '',
        ];

        foreach ($invalidRelationships as $invalidRelationship) {
            $data = [
                'parentId' => 100,
                'studentId' => 200,
                'parentRoleId' => 10,
                'studentRoleId' => 20,
                'relationship' => $invalidRelationship,
            ];

            // Expect
            $this->expectException(\ValueError::class);

            // Act
            ParentStudentMapper::toDomain($data);
        }
    }

    // ==================== TO PARENT CHILDREN RESPONSE TESTS ====================

    #[Test]
    public function to_parent_children_response_creates_correct_response(): void
    {
        // Arrange
        $data = [
            'parentId' => 500,
            'parentName' => 'John Doe',
            'childrenData' => [
                [
                    'studentId' => 600,
                    'studentName' => 'Jane Doe',
                    'grade' => '5th',
                    'relationship' => 'FATHER',
                ],
                [
                    'studentId' => 601,
                    'studentName' => 'Jim Doe',
                    'grade' => '3rd',
                    'relationship' => 'FATHER',
                ],
            ],
        ];

        // Act
        $result = ParentStudentMapper::toParentChildrenResponse($data);

        // Assert
        $this->assertInstanceOf(ParentChildrenResponse::class, $result);
        $this->assertEquals(500, $result->parentId);
        $this->assertEquals('John Doe', $result->parentName);
        $this->assertCount(2, $result->childrenData);
        $this->assertEquals('Jane Doe', $result->childrenData[0]['studentName']);
        $this->assertEquals('Jim Doe', $result->childrenData[1]['studentName']);
    }

    #[Test]
    public function to_parent_children_response_with_empty_children_data(): void
    {
        // Arrange
        $data = [
            'parentId' => 500,
            'parentName' => 'John Doe',
            'childrenData' => [], // Empty array
        ];

        // Act
        $result = ParentStudentMapper::toParentChildrenResponse($data);

        // Assert
        $this->assertInstanceOf(ParentChildrenResponse::class, $result);
        $this->assertEquals(500, $result->parentId);
        $this->assertEquals('John Doe', $result->parentName);
        $this->assertEmpty($result->childrenData);
    }

    #[Test]
    public function to_parent_children_response_without_children_data_key(): void
    {
        // Arrange
        $data = [
            'parentId' => 500,
            'parentName' => 'John Doe',
            // 'childrenData' key is missing
        ];

        // Act
        $result = ParentStudentMapper::toParentChildrenResponse($data);

        // Assert
        $this->assertInstanceOf(ParentChildrenResponse::class, $result);
        $this->assertEquals(500, $result->parentId);
        $this->assertEquals('John Doe', $result->parentName);
        $this->assertEmpty($result->childrenData); // Default empty array
    }

    #[Test]
    public function to_parent_children_response_with_complex_children_data(): void
    {
        // Arrange
        $data = [
            'parentId' => 700,
            'parentName' => 'Maria Garcia',
            'childrenData' => [
                [
                    'studentId' => 701,
                    'studentName' => 'Carlos Garcia',
                    'grade' => '10th',
                    'relationship' => 'MOTHER',
                    'birthDate' => '2010-05-15',
                    'enrollmentStatus' => 'active',
                    'lastPaymentDate' => '2024-01-10',
                ],
                [
                    'studentId' => 702,
                    'studentName' => 'Ana Garcia',
                    'grade' => '8th',
                    'relationship' => 'MOTHER',
                    'birthDate' => '2012-08-20',
                    'enrollmentStatus' => 'active',
                    'lastPaymentDate' => '2024-01-10',
                ],
            ],
        ];

        // Act
        $result = ParentStudentMapper::toParentChildrenResponse($data);

        // Assert
        $this->assertInstanceOf(ParentChildrenResponse::class, $result);
        $this->assertCount(2, $result->childrenData);

        // Verificar estructura completa del primer hijo
        $firstChild = $result->childrenData[0];
        $this->assertEquals(701, $firstChild['studentId']);
        $this->assertEquals('Carlos Garcia', $firstChild['studentName']);
        $this->assertEquals('10th', $firstChild['grade']);
        $this->assertEquals('MOTHER', $firstChild['relationship']);
        $this->assertEquals('2010-05-15', $firstChild['birthDate']);
        $this->assertEquals('active', $firstChild['enrollmentStatus']);
        $this->assertEquals('2024-01-10', $firstChild['lastPaymentDate']);
    }

    // ==================== TO STUDENT PARENTS RESPONSE TESTS ====================

    #[Test]
    public function to_student_parents_response_creates_correct_response(): void
    {
        // Arrange
        $data = [
            'studentId' => 300,
            'studentName' => 'Student Name',
            'parentsData' => [
                [
                    'parentId' => 400,
                    'parentName' => 'Parent One',
                    'email' => 'parent1@example.com',
                    'relationship' => 'FATHER',
                    'phone' => '+1234567890',
                ],
                [
                    'parentId' => 401,
                    'parentName' => 'Parent Two',
                    'email' => 'parent2@example.com',
                    'relationship' => 'MOTHER',
                    'phone' => '+0987654321',
                ],
            ],
        ];

        // Act
        $result = ParentStudentMapper::toStudentParentsResponse($data);

        // Assert
        $this->assertInstanceOf(StudentParentsResponse::class, $result);
        $this->assertEquals(300, $result->studentId);
        $this->assertEquals('Student Name', $result->studentName);
        $this->assertCount(2, $result->parentsData);
        $this->assertEquals('Parent One', $result->parentsData[0]['parentName']);
        $this->assertEquals('Parent Two', $result->parentsData[1]['parentName']);
        $this->assertEquals('FATHER', $result->parentsData[0]['relationship']);
        $this->assertEquals('MOTHER', $result->parentsData[1]['relationship']);
    }

    #[Test]
    public function to_student_parents_response_with_empty_parents_data(): void
    {
        // Arrange
        $data = [
            'studentId' => 300,
            'studentName' => 'Student Without Parents',
            'parentsData' => [], // Empty array
        ];

        // Act
        $result = ParentStudentMapper::toStudentParentsResponse($data);

        // Assert
        $this->assertInstanceOf(StudentParentsResponse::class, $result);
        $this->assertEquals(300, $result->studentId);
        $this->assertEquals('Student Without Parents', $result->studentName);
        $this->assertEmpty($result->parentsData);
    }

    #[Test]
    public function to_student_parents_response_without_parents_data_key(): void
    {
        // Arrange
        $data = [
            'studentId' => 300,
            'studentName' => 'Student Name',
            // 'parentsData' key is missing
        ];

        // Act
        $result = ParentStudentMapper::toStudentParentsResponse($data);

        // Assert
        $this->assertInstanceOf(StudentParentsResponse::class, $result);
        $this->assertEquals(300, $result->studentId);
        $this->assertEquals('Student Name', $result->studentName);
        $this->assertEmpty($result->parentsData); // Default empty array
    }

    #[Test]
    public function to_student_parents_response_with_single_parent(): void
    {
        // Arrange - Student with only one parent
        $data = [
            'studentId' => 350,
            'studentName' => 'Single Parent Student',
            'parentsData' => [
                [
                    'parentId' => 450,
                    'parentName' => 'Single Parent',
                    'email' => 'single@example.com',
                    'relationship' => 'GUARDIAN',
                ],
            ],
        ];

        // Act
        $result = ParentStudentMapper::toStudentParentsResponse($data);

        // Assert
        $this->assertInstanceOf(StudentParentsResponse::class, $result);
        $this->assertEquals(350, $result->studentId);
        $this->assertEquals('Single Parent Student', $result->studentName);
        $this->assertCount(1, $result->parentsData);
        $this->assertEquals('Single Parent', $result->parentsData[0]['parentName']);
        $this->assertEquals('GUARDIAN', $result->parentsData[0]['relationship']);
    }

    // ==================== EDGE CASE TESTS ====================

    #[Test]
    public function relationship_type_case_sensitivity(): void
    {
        // RelationshipType es case-sensitive
        $testCases = [
            ['input' => 'FATHER', 'shouldWork' => false],
            ['input' => 'father', 'shouldWork' => false], // lowercase
            ['input' => 'Mother', 'shouldWork' => false], // capitalized
            ['input' => 'MOTHER', 'shouldWork' => false],
            ['input' => 'GUARDIAN', 'shouldWork' => false],
            ['input' => 'guardian', 'shouldWork' => false],
            ['input' => 'OTHER', 'shouldWork' => false],
            ['input' => 'other', 'shouldWork' => false],
            ['input' => 'PADRE', 'shouldWork' => true],
            ['input' => 'MADRE', 'shouldWork' => true],
            ['input' => 'TUTOR', 'shouldWork' => true],
            ['input' => 'TUTOR_LEGAL', 'shouldWork' => true],
        ];

        foreach ($testCases as $case) {
            $data = [
                'parentId' => 1,
                'studentId' => 2,
                'parentRoleId' => 3,
                'studentRoleId' => 4,
                'relationship' => $case['input'],
            ];

            if ($case['shouldWork']) {
                $result = ParentStudentMapper::toDomain($data);
                $this->assertEquals($case['input'], $result->relationship->value);
            } else {
                $this->expectException(\ValueError::class);
                ParentStudentMapper::toDomain($data);
            }
        }
    }

    #[Test]
    public function handles_large_ids(): void
    {
        // Arrange - IDs grandes
        $data = [
            'parentId' => PHP_INT_MAX,
            'studentId' => PHP_INT_MAX - 1,
            'parentRoleId' => PHP_INT_MAX - 2,
            'studentRoleId' => PHP_INT_MAX - 3,
            'relationship' => 'PADRE',
        ];

        // Act
        $result = ParentStudentMapper::toDomain($data);

        // Assert
        $this->assertInstanceOf(ParentStudent::class, $result);
        $this->assertEquals(PHP_INT_MAX, $result->parentId);
        $this->assertEquals(PHP_INT_MAX - 1, $result->studentId);
        $this->assertEquals(PHP_INT_MAX - 2, $result->parentRoleId);
        $this->assertEquals(PHP_INT_MAX - 3, $result->studentRoleId);
    }

    #[Test]
    public function response_mappers_preserve_data_structure(): void
    {
        // Test que los mappers de respuesta no modifican la estructura de datos
        $childrenData = [
            [
                'studentId' => 1,
                'studentName' => 'Child 1',
                'customField' => 'customValue1',
                'nested' => ['level1' => ['level2' => 'value']],
            ],
            [
                'studentId' => 2,
                'studentName' => 'Child 2',
                'customField' => 'customValue2',
                'anotherField' => 123,
            ],
        ];

        $parentData = [
            'parentId' => 100,
            'parentName' => 'Test Parent',
            'childrenData' => $childrenData,
        ];

        $result = ParentStudentMapper::toParentChildrenResponse($parentData);

        // Los datos deberían preservarse exactamente como se pasaron
        $this->assertEquals($childrenData[0]['customField'], $result->childrenData[0]['customField']);
        $this->assertEquals($childrenData[0]['nested'], $result->childrenData[0]['nested']);
        $this->assertEquals($childrenData[1]['anotherField'], $result->childrenData[1]['anotherField']);
    }

    #[Test]
    public function handles_special_characters_in_names(): void
    {
        // Test con caracteres especiales en nombres
        $testCases = [
            [
                'parentName' => 'María José Pérez-López',
                'studentName' => 'Juan Carlos González',
                'childrenNames' => ['Ana Sofía', 'José María'],
                'parentNames' => ['Carlos Ñandú', 'María de los Ángeles'],
            ],
            [
                'parentName' => 'O\'Connor',
                'studentName' => 'Smith-Johnson',
                'childrenNames' => ['Lee Jr.', 'van der Berg'],
                'parentNames' => ['Dr. Zhang', 'Prof. Müller'],
            ],
            [
                'parentName' => 'نام نام خانوادگی', // Persian/Arabic
                'studentName' => '姓 名', // Chinese
                'childrenNames' => ['児童 名前'], // Japanese
                'parentNames' => ['Родитель Имя'], // Russian
            ],
        ];

        foreach ($testCases as $case) {
            // Test ParentChildrenResponse
            $parentChildrenData = [
                'parentId' => 1,
                'parentName' => $case['parentName'],
                'childrenData' => [
                    [
                        'studentId' => 1,
                        'studentName' => $case['childrenNames'][0],
                    ],
                ],
            ];

            $parentChildrenResult = ParentStudentMapper::toParentChildrenResponse($parentChildrenData);
            $this->assertEquals($case['parentName'], $parentChildrenResult->parentName);
            $this->assertEquals($case['childrenNames'][0], $parentChildrenResult->childrenData[0]['studentName']);

            // Test StudentParentsResponse
            $studentParentsData = [
                'studentId' => 1,
                'studentName' => $case['studentName'],
                'parentsData' => [
                    [
                        'parentId' => 1,
                        'parentName' => $case['parentNames'][0],
                    ],
                ],
            ];

            $studentParentsResult = ParentStudentMapper::toStudentParentsResponse($studentParentsData);
            $this->assertEquals($case['studentName'], $studentParentsResult->studentName);
            $this->assertEquals($case['parentNames'][0], $studentParentsResult->parentsData[0]['parentName']);
        }
    }

    #[Test]
    public function mapper_works_with_real_world_scenarios(): void
    {
        // Escenario 1: Padre con múltiples hijos
        $parentScenario = [
            'parentId' => 1001,
            'parentName' => 'Robert Johnson',
            'childrenData' => [
                [
                    'studentId' => 2001,
                    'studentName' => 'Emily Johnson',
                    'grade' => '9th',
                    'relationship' => 'FATHER',
                    'school' => 'Central High',
                    'birthDate' => '2009-03-15',
                ],
                [
                    'studentId' => 2002,
                    'studentName' => 'Michael Johnson',
                    'grade' => '7th',
                    'relationship' => 'FATHER',
                    'school' => 'Central Middle',
                    'birthDate' => '2011-07-22',
                ],
                [
                    'studentId' => 2003,
                    'studentName' => 'Sarah Johnson',
                    'grade' => '4th',
                    'relationship' => 'FATHER',
                    'school' => 'Elementary School',
                    'birthDate' => '2015-11-30',
                ],
            ],
        ];

        $parentResult = ParentStudentMapper::toParentChildrenResponse($parentScenario);
        $this->assertCount(3, $parentResult->childrenData);
        $this->assertEquals('Robert Johnson', $parentResult->parentName);

        // Escenario 2: Estudiante con padres separados
        $studentScenario = [
            'studentId' => 3001,
            'studentName' => 'David Wilson',
            'parentsData' => [
                [
                    'parentId' => 4001,
                    'parentName' => 'Karen Wilson',
                    'email' => 'karen@example.com',
                    'phone' => '+1-555-0101',
                    'relationship' => 'MOTHER',
                    'primaryContact' => true,
                ],
                [
                    'parentId' => 4002,
                    'parentName' => 'James Wilson',
                    'email' => 'james@example.com',
                    'phone' => '+1-555-0102',
                    'relationship' => 'FATHER',
                    'primaryContact' => false,
                ],
            ],
        ];

        $studentResult = ParentStudentMapper::toStudentParentsResponse($studentScenario);
        $this->assertCount(2, $studentResult->parentsData);
        $this->assertEquals('David Wilson', $studentResult->studentName);
        $this->assertTrue($studentResult->parentsData[0]['primaryContact']);
        $this->assertFalse($studentResult->parentsData[1]['primaryContact']);
    }

}
