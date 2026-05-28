<?php

namespace Tests\Unit\Domain\Entities;

use Tests\Unit\Domain\BaseDomainTestCase;
use App\Core\Domain\Entities\StudentDetail;
use PHPUnit\Framework\Attributes\Test;

class StudentDetailTest extends BaseDomainTestCase
{
    #[Test]
    public function it_can_be_instantiated()
    {
        $studentDetail = new StudentDetail(
            user_id: 1
        );

        $this->assertInstanceOf(StudentDetail::class, $studentDetail);
    }

    #[Test]
    public function it_can_be_instantiated_with_all_parameters()
    {
        $studentDetail = new StudentDetail(
            user_id: 1,
            id: 100,
            career_id: 5,
            n_control: 12345678,
            semestre: 3,
            group: 'A',
            workshop: 'Taller de programación'
        );

        $this->assertInstanceOf(StudentDetail::class, $studentDetail);
        $this->assertEquals(1, $studentDetail->user_id);
        $this->assertEquals(100, $studentDetail->id);
        $this->assertEquals(5, $studentDetail->career_id);
        $this->assertEquals(12345678, $studentDetail->n_control);
        $this->assertEquals(3, $studentDetail->semestre);
        $this->assertEquals('A', $studentDetail->group);
        $this->assertEquals('Taller de programación', $studentDetail->workshop);
    }

    #[Test]
    public function it_has_required_attributes()
    {
        $studentDetail = new StudentDetail(
            user_id: 42
        );

        $this->assertEquals(42, $studentDetail->user_id);
        $this->assertNull($studentDetail->id);
        $this->assertNull($studentDetail->career_id);
        $this->assertNull($studentDetail->n_control);
        $this->assertNull($studentDetail->semestre);
        $this->assertNull($studentDetail->group);
        $this->assertNull($studentDetail->workshop);
    }

    #[Test]
    public function it_accepts_valid_data()
    {
        $studentDetail = new StudentDetail(
            user_id: 10,
            id: 50,
            career_id: 3,
            n_control: 87654321,
            semestre: 5,
            group: 'B',
            workshop: null
        );

        $this->assertInstanceOf(StudentDetail::class, $studentDetail);
        $this->assertEquals(10, $studentDetail->user_id);
        $this->assertEquals(50, $studentDetail->id);
        $this->assertEquals(3, $studentDetail->career_id);
        $this->assertEquals(87654321, $studentDetail->n_control);
        $this->assertEquals(5, $studentDetail->semestre);
        $this->assertEquals('B', $studentDetail->group);
        $this->assertNull($studentDetail->workshop);
    }

    #[Test]
    public function it_sets_default_values_for_optional_parameters()
    {
        $studentDetail = new StudentDetail(
            user_id: 99
        );

        $this->assertEquals(99, $studentDetail->user_id);
        $this->assertNull($studentDetail->id);
        $this->assertNull($studentDetail->career_id);
        $this->assertNull($studentDetail->n_control);
        $this->assertNull($studentDetail->semestre);
        $this->assertNull($studentDetail->group);
        $this->assertNull($studentDetail->workshop);
    }

    #[Test]
    public function it_can_promote_semester()
    {
        $studentDetail = new StudentDetail(
            user_id: 1,
            semestre: 3
        );

        $studentDetail->promote();

        $this->assertEquals(4, $studentDetail->semestre);
    }

    #[Test]
    public function it_does_not_promote_when_semester_is_null()
    {
        $studentDetail = new StudentDetail(
            user_id: 1,
            semestre: null
        );

        $studentDetail->promote();

        $this->assertNull($studentDetail->semestre);
    }

    #[Test]
    public function it_can_promote_multiple_times()
    {
        $studentDetail = new StudentDetail(
            user_id: 1,
            semestre: 1
        );

        $studentDetail->promote();
        $studentDetail->promote();
        $studentDetail->promote();

        $this->assertEquals(4, $studentDetail->semestre);
    }

    #[Test]
    public function it_promotes_from_semester_1_to_2()
    {
        $studentDetail = new StudentDetail(
            user_id: 1,
            semestre: 1
        );

        $studentDetail->promote();

        $this->assertEquals(2, $studentDetail->semestre);
    }

    #[Test]
    public function it_accepts_various_group_formats()
    {
        $groups = ['A', 'B', 'C', 'D', '1A', '2B', 'MAT-101'];

        foreach ($groups as $group) {
            $studentDetail = new StudentDetail(
                user_id: 1,
                group: $group
            );

            $this->assertEquals($group, $studentDetail->group);
        }
    }

    #[Test]
    public function it_accepts_various_workshop_names()
    {
        $workshops = [
            'Taller de programación',
            'Laboratorio de física',
            'Seminario de investigación',
            null,
            'Taller de inglés'
        ];

        foreach ($workshops as $workshop) {
            $studentDetail = new StudentDetail(
                user_id: 1,
                workshop: $workshop
            );

            $this->assertEquals($workshop, $studentDetail->workshop);
        }
    }

    #[Test]
    public function it_accepts_large_n_control_numbers()
    {
        $studentDetail = new StudentDetail(
            user_id: 1,
            n_control: 99999999
        );

        $this->assertEquals(99999999, $studentDetail->n_control);
    }

    #[Test]
    public function it_accepts_small_n_control_numbers()
    {
        $studentDetail = new StudentDetail(
            user_id: 1,
            n_control: 1000
        );

        $this->assertEquals(1000, $studentDetail->n_control);
    }

    #[Test]
    public function it_handles_different_career_ids()
    {
        $careerIds = [1, 2, 3, 10, 25, null];

        foreach ($careerIds as $careerId) {
            $studentDetail = new StudentDetail(
                user_id: 1,
                career_id: $careerId
            );

            $this->assertEquals($careerId, $studentDetail->career_id);
        }
    }

    #[Test]
    public function it_handles_high_semester_numbers()
    {
        $studentDetail = new StudentDetail(
            user_id: 1,
            semestre: 10
        );

        $studentDetail->promote();

        $this->assertEquals(11, $studentDetail->semestre);
    }

    #[Test]
    public function it_maintains_other_properties_when_promoting()
    {
        $studentDetail = new StudentDetail(
            user_id: 1,
            id: 50,
            career_id: 3,
            n_control: 12345678,
            semestre: 2,
            group: 'A',
            workshop: 'Taller'
        );

        $studentDetail->promote();

        $this->assertEquals(3, $studentDetail->semestre);
        $this->assertEquals(1, $studentDetail->user_id);
        $this->assertEquals(50, $studentDetail->id);
        $this->assertEquals(3, $studentDetail->career_id);
        $this->assertEquals(12345678, $studentDetail->n_control);
        $this->assertEquals('A', $studentDetail->group);
        $this->assertEquals('Taller', $studentDetail->workshop);
    }

    #[Test]
    public function it_can_be_converted_to_json()
    {
        $studentDetail = new StudentDetail(
            user_id: 1,
            id: 100,
            career_id: 5,
            n_control: 12345678,
            semestre: 3,
            group: 'A'
        );

        $json = json_encode($studentDetail);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals(1, $decoded['user_id']);
        $this->assertEquals(100, $decoded['id']);
        $this->assertEquals(5, $decoded['career_id']);
        $this->assertEquals(12345678, $decoded['n_control']);
        $this->assertEquals(3, $decoded['semestre']);
        $this->assertEquals('A', $decoded['group']);
        $this->assertNull($decoded['workshop']);
    }

    #[Test]
    public function it_handles_json_serialization_with_all_null_values()
    {
        $studentDetail = new StudentDetail(
            user_id: 1
        );

        $json = json_encode($studentDetail);
        $decoded = json_decode($json, true);

        $this->assertEquals(1, $decoded['user_id']);
        $this->assertNull($decoded['id']);
        $this->assertNull($decoded['career_id']);
        $this->assertNull($decoded['n_control']);
        $this->assertNull($decoded['semestre']);
        $this->assertNull($decoded['group']);
        $this->assertNull($decoded['workshop']);
    }

    #[Test]
    public function it_accepts_student_with_only_user_id_and_semester()
    {
        $studentDetail = new StudentDetail(
            user_id: 33,
            semestre: 6
        );

        $this->assertEquals(33, $studentDetail->user_id);
        $this->assertEquals(6, $studentDetail->semestre);
        $this->assertNull($studentDetail->id);
        $this->assertNull($studentDetail->career_id);
        $this->assertNull($studentDetail->n_control);
        $this->assertNull($studentDetail->group);
        $this->assertNull($studentDetail->workshop);
    }

    #[Test]
    public function it_accepts_student_with_only_user_id_and_career()
    {
        $studentDetail = new StudentDetail(
            user_id: 77,
            career_id: 8
        );

        $this->assertEquals(77, $studentDetail->user_id);
        $this->assertEquals(8, $studentDetail->career_id);
        $this->assertNull($studentDetail->semestre);
    }
}
