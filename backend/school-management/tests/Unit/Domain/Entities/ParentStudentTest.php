<?php

namespace Tests\Unit\Domain\Entities;

use App\Core\Domain\Enum\User\RelationshipType;
use Tests\Unit\Domain\BaseDomainTestCase;
use App\Core\Domain\Entities\ParentStudent;
use PHPUnit\Framework\Attributes\Test;

class ParentStudentTest extends BaseDomainTestCase
{
    #[Test]
    public function it_can_be_instantiated()
    {
        $parentStudent = new ParentStudent(
            parentId: 100,
            studentId: 200,
            parentRoleId: 5,
            studentRoleId: 3,
            relationship: RelationshipType::PADRE
        );

        $this->assertInstanceOf(ParentStudent::class, $parentStudent);
    }

    #[Test]
    public function it_can_be_instantiated_with_null_relationship()
    {
        $parentStudent = new ParentStudent(
            parentId: 150,
            studentId: 250,
            parentRoleId: 5,
            studentRoleId: 3,
            relationship: null
        );

        $this->assertInstanceOf(ParentStudent::class, $parentStudent);
        $this->assertNull($parentStudent->relationship);
    }

    #[Test]
    public function it_has_required_attributes()
    {
        $parentStudent = new ParentStudent(
            parentId: 300,
            studentId: 400,
            parentRoleId: 10,
            studentRoleId: 7,
            relationship: RelationshipType::MADRE
        );

        $this->assertEquals(300, $parentStudent->parentId);
        $this->assertEquals(400, $parentStudent->studentId);
        $this->assertEquals(10, $parentStudent->parentRoleId);
        $this->assertEquals(7, $parentStudent->studentRoleId);
        $this->assertEquals(RelationshipType::MADRE, $parentStudent->relationship);
    }

    #[Test]
    public function it_accepts_valid_data()
    {
        $parentStudent = new ParentStudent(
            parentId: 500,
            studentId: 600,
            parentRoleId: 15,
            studentRoleId: 12,
            relationship: RelationshipType::TUTOR
        );

        $this->assertInstanceOf(ParentStudent::class, $parentStudent);
        $this->assertEquals(500, $parentStudent->parentId);
        $this->assertEquals(600, $parentStudent->studentId);
        $this->assertEquals(15, $parentStudent->parentRoleId);
        $this->assertEquals(12, $parentStudent->studentRoleId);
        $this->assertEquals(RelationshipType::TUTOR, $parentStudent->relationship);
    }

    #[Test]
    public function it_has_readonly_properties()
    {
        $parentStudent = new ParentStudent(
            parentId: 700,
            studentId: 800,
            parentRoleId: 20,
            studentRoleId: 18,
            relationship: RelationshipType::TUTOR
        );

        $this->assertEquals(700, $parentStudent->parentId);
        $this->assertEquals(800, $parentStudent->studentId);
        $this->assertEquals(20, $parentStudent->parentRoleId);
        $this->assertEquals(18, $parentStudent->studentRoleId);
        $this->assertEquals(RelationshipType::TUTOR, $parentStudent->relationship);
    }

    #[Test]
    public function it_accepts_all_relationship_types()
    {
        $relationships = [
            RelationshipType::PADRE,
            RelationshipType::MADRE,
            RelationshipType::TUTOR_LEGAL,
            RelationshipType::TUTOR,
            null,
        ];

        foreach ($relationships as $relationship) {
            $parentStudent = new ParentStudent(
                parentId: 1,
                studentId: 2,
                parentRoleId: 5,
                studentRoleId: 3,
                relationship: $relationship
            );

            $this->assertEquals($relationship, $parentStudent->relationship);
        }
    }

    #[Test]
    public function it_accepts_different_ids()
    {
        $testCases = [
            ['parentId' => 1, 'studentId' => 2, 'parentRoleId' => 3, 'studentRoleId' => 4],
            ['parentId' => 1000, 'studentId' => 2000, 'parentRoleId' => 5, 'studentRoleId' => 6],
            ['parentId' => 999999, 'studentId' => 888888, 'parentRoleId' => 7, 'studentRoleId' => 8],
            ['parentId' => 0, 'studentId' => 0, 'parentRoleId' => 0, 'studentRoleId' => 0], // Edge case
        ];

        foreach ($testCases as $test) {
            $parentStudent = new ParentStudent(
                parentId: $test['parentId'],
                studentId: $test['studentId'],
                parentRoleId: $test['parentRoleId'],
                studentRoleId: $test['studentRoleId'],
                relationship: RelationshipType::PADRE
            );

            $this->assertEquals($test['parentId'], $parentStudent->parentId);
            $this->assertEquals($test['studentId'], $parentStudent->studentId);
            $this->assertEquals($test['parentRoleId'], $parentStudent->parentRoleId);
            $this->assertEquals($test['studentRoleId'], $parentStudent->studentRoleId);
        }
    }

    #[Test]
    public function it_handles_same_person_as_parent_and_student()
    {
        $parentStudent = new ParentStudent(
            parentId: 100,
            studentId: 100,
            parentRoleId: 5,
            studentRoleId: 3,
            relationship: RelationshipType::TUTOR
        );

        $this->assertEquals(100, $parentStudent->parentId);
        $this->assertEquals(100, $parentStudent->studentId);
        $this->assertNotEquals($parentStudent->parentRoleId, $parentStudent->studentRoleId);
    }


    #[Test]
    public function it_can_be_converted_to_json()
    {
        $parentStudent = new ParentStudent(
            parentId: 3000,
            studentId: 4000,
            parentRoleId: 30,
            studentRoleId: 20,
            relationship: RelationshipType::PADRE
        );

        $json = json_encode($parentStudent);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals(3000, $decoded['parentId']);
        $this->assertEquals(4000, $decoded['studentId']);
        $this->assertEquals(30, $decoded['parentRoleId']);
        $this->assertEquals(20, $decoded['studentRoleId']);
        $this->assertEquals(RelationshipType::PADRE->value, $decoded['relationship']);
    }

    #[Test]
    public function it_can_be_converted_to_json_with_null_relationship()
    {
        $parentStudent = new ParentStudent(
            parentId: 5000,
            studentId: 6000,
            parentRoleId: 35,
            studentRoleId: 25,
            relationship: null
        );

        $json = json_encode($parentStudent);
        $decoded = json_decode($json, true);

        $this->assertJson($json);
        $this->assertEquals(5000, $decoded['parentId']);
        $this->assertEquals(6000, $decoded['studentId']);
        $this->assertEquals(35, $decoded['parentRoleId']);
        $this->assertEquals(25, $decoded['studentRoleId']);
        $this->assertNull($decoded['relationship']);
    }

    #[Test]
    public function it_handles_json_serialization_correctly()
    {
        $parentStudent = new ParentStudent(
            parentId: 10000,
            studentId: 20000,
            parentRoleId: 50,
            studentRoleId: 40,
            relationship: RelationshipType::TUTOR
        );

        $json = json_encode($parentStudent);
        $decoded = json_decode($json, true);

        $this->assertCount(5, $decoded);
        $this->assertArrayHasKey('parentId', $decoded);
        $this->assertArrayHasKey('studentId', $decoded);
        $this->assertArrayHasKey('parentRoleId', $decoded);
        $this->assertArrayHasKey('studentRoleId', $decoded);
        $this->assertArrayHasKey('relationship', $decoded);

        $this->assertIsInt($decoded['parentId']);
        $this->assertIsInt($decoded['studentId']);
        $this->assertIsInt($decoded['parentRoleId']);
        $this->assertIsInt($decoded['studentRoleId']);
        $this->assertIsString($decoded['relationship']);
    }

    #[Test]
    public function it_can_be_compared_by_all_properties()
    {
        $ps1 = new ParentStudent(
            parentId: 100,
            studentId: 200,
            parentRoleId: 5,
            studentRoleId: 3,
            relationship: RelationshipType::PADRE
        );

        $ps2 = new ParentStudent(
            parentId: 100,
            studentId: 200,
            parentRoleId: 5,
            studentRoleId: 3,
            relationship: RelationshipType::PADRE
        );

        $ps3 = new ParentStudent(
            parentId: 101,
            studentId: 200,
            parentRoleId: 5,
            studentRoleId: 3,
            relationship: RelationshipType::PADRE
        );

        $ps4 = new ParentStudent(
            parentId: 100,
            studentId: 200,
            parentRoleId: 5,
            studentRoleId: 3,
            relationship: RelationshipType::MADRE
        );

        $this->assertEquals($ps1->parentId, $ps2->parentId);
        $this->assertEquals($ps1->studentId, $ps2->studentId);
        $this->assertEquals($ps1->parentRoleId, $ps2->parentRoleId);
        $this->assertEquals($ps1->studentRoleId, $ps2->studentRoleId);
        $this->assertEquals($ps1->relationship, $ps2->relationship);

        $this->assertNotEquals($ps1->parentId, $ps3->parentId);
        $this->assertNotEquals($ps1->relationship, $ps4->relationship);
    }

    #[Test]
    public function it_can_be_used_as_immutable_value_object()
    {
        $parentStudent = new ParentStudent(
            parentId: 150,
            studentId: 250,
            parentRoleId: 8,
            studentRoleId: 4,
            relationship: RelationshipType::MADRE
        );

        $this->assertEquals(150, $parentStudent->parentId);
        $this->assertEquals(250, $parentStudent->studentId);
        $this->assertEquals(8, $parentStudent->parentRoleId);
        $this->assertEquals(4, $parentStudent->studentRoleId);
        $this->assertEquals(RelationshipType::MADRE, $parentStudent->relationship);

        $modified = new ParentStudent(
            parentId: 150,
            studentId: 250,
            parentRoleId: 8,
            studentRoleId: 4,
            relationship: RelationshipType::PADRE
        );

        $this->assertEquals(RelationshipType::PADRE, $modified->relationship);
        $this->assertEquals($parentStudent->parentId, $modified->parentId);
        $this->assertEquals($parentStudent->studentId, $modified->studentId);
    }

    #[Test]
    public function it_handles_edge_cases_for_ids()
    {
        $edgeCases = [
            ['parentId' => PHP_INT_MAX, 'studentId' => PHP_INT_MAX - 1],
            ['parentId' => 1, 'studentId' => PHP_INT_MAX],
            ['parentId' => 0, 'studentId' => 0],
            ['parentId' => -1, 'studentId' => -2],
        ];

        foreach ($edgeCases as $case) {
            $parentStudent = new ParentStudent(
                parentId: $case['parentId'],
                studentId: $case['studentId'],
                parentRoleId: 1,
                studentRoleId: 2,
                relationship: RelationshipType::TUTOR
            );

            $this->assertEquals($case['parentId'], $parentStudent->parentId);
            $this->assertEquals($case['studentId'], $parentStudent->studentId);
        }
    }

    #[Test]
    public function it_handles_different_role_id_combinations()
    {
        $combinations = [
            ['parentRoleId' => 1, 'studentRoleId' => 2],
            ['parentRoleId' => 5, 'studentRoleId' => 5],
            ['parentRoleId' => 100, 'studentRoleId' => 200],
            ['parentRoleId' => 0, 'studentRoleId' => 0],
        ];

        foreach ($combinations as $roles) {
            $parentStudent = new ParentStudent(
                parentId: 100,
                studentId: 200,
                parentRoleId: $roles['parentRoleId'],
                studentRoleId: $roles['studentRoleId'],
                relationship: RelationshipType::TUTOR
            );

            $this->assertEquals($roles['parentRoleId'], $parentStudent->parentRoleId);
            $this->assertEquals($roles['studentRoleId'], $parentStudent->studentRoleId);
        }
    }

    #[Test]
    public function it_provides_consistent_string_representation()
    {
        $parentStudent = new ParentStudent(
            parentId: 999,
            studentId: 888,
            parentRoleId: 7,
            studentRoleId: 3,
            relationship: RelationshipType::TUTOR_LEGAL
        );

        $string = "ParentStudent[parent:999, student:888, relationship:tutor_legal]";

        $jsonString = json_encode($parentStudent);
        $this->assertStringContainsString('"parentId":999', $jsonString);
        $this->assertStringContainsString('"studentId":888', $jsonString);
        $this->assertStringContainsString('"relationship":"tutor_legal"', $jsonString);
    }

    #[Test]
    public function it_can_be_used_in_collections()
    {
        $relationships = [
            new ParentStudent(parentId: 1, studentId: 10, parentRoleId: 5, studentRoleId: 3, relationship: RelationshipType::PADRE),
            new ParentStudent(parentId: 2, studentId: 20, parentRoleId: 5, studentRoleId: 3, relationship: RelationshipType::MADRE),
            new ParentStudent(parentId: 3, studentId: 30, parentRoleId: 5, studentRoleId: 3, relationship: RelationshipType::TUTOR),
            new ParentStudent(parentId: 4, studentId: 40, parentRoleId: 5, studentRoleId: 3, relationship: null),
        ];

        $this->assertCount(4, $relationships);
        $this->assertInstanceOf(ParentStudent::class, $relationships[0]);
        $this->assertInstanceOf(ParentStudent::class, $relationships[1]);
        $this->assertInstanceOf(ParentStudent::class, $relationships[2]);
        $this->assertInstanceOf(ParentStudent::class, $relationships[3]);

        $this->assertEquals(RelationshipType::PADRE, $relationships[0]->relationship);
        $this->assertEquals(RelationshipType::MADRE, $relationships[1]->relationship);
        $this->assertEquals(RelationshipType::TUTOR, $relationships[2]->relationship);
        $this->assertNull($relationships[3]->relationship);
    }

    #[Test]
    public function it_can_be_instantiated_with_minimal_data_and_relationship()
    {
        $parentStudent = new ParentStudent(
            parentId: 50,
            studentId: 60,
            parentRoleId: 5,
            studentRoleId: 3,
            relationship: RelationshipType::MADRE
        );

        $this->assertEquals(50, $parentStudent->parentId);
        $this->assertEquals(60, $parentStudent->studentId);
        $this->assertEquals(5, $parentStudent->parentRoleId);
        $this->assertEquals(3, $parentStudent->studentRoleId);
        $this->assertEquals(RelationshipType::MADRE, $parentStudent->relationship);
    }
}
