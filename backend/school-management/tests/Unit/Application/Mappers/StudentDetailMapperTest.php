<?php

namespace Tests\Unit\Application\Mappers;

use App\Core\Application\DTO\Request\StudentDetail\CreateStudentDetailDTO;
use App\Core\Application\Mappers\StudentDetailMapper;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StudentDetailMapperTest extends TestCase
{
    #[Test]
    public function it_maps_valid_data_to_create_student_detail_dto(): void
    {
        // Arrange
        $data = [
            'user_id' => 1,
            'career_id' => 2,
            'n_control' => '19201134',
            'semestre' => 5,
            'group' => 'A',
            'workshop' => 'Taller de Programación',
        ];

        // Act
        $result = StudentDetailMapper::toCreateStudentDetailDTO($data);

        // Assert
        $this->assertInstanceOf(CreateStudentDetailDTO::class, $result);
        $this->assertEquals(1, $result->user_id);
        $this->assertEquals(2, $result->career_id);
        $this->assertEquals('19201134', $result->n_control);
        $this->assertEquals(5, $result->semestre);
        $this->assertEquals('A', $result->group);
        $this->assertEquals('Taller de Programación', $result->workshop);
    }

    #[Test]
    public function it_handles_empty_string_values(): void
    {
        // Arrange
        $data = [
            'user_id' => 1,
            'career_id' => 2,
            'n_control' => '',
            'semestre' => 0,
            'group' => '',
            'workshop' => '',
        ];

        // Act
        $result = StudentDetailMapper::toCreateStudentDetailDTO($data);

        // Assert
        $this->assertInstanceOf(CreateStudentDetailDTO::class, $result);
        $this->assertEquals('', $result->n_control);
        $this->assertEquals(0, $result->semestre);
        $this->assertEquals('', $result->group);
        $this->assertEquals('', $result->workshop);
    }

    #[Test]
    public function it_handles_special_characters_in_n_control(): void
    {
        // Arrange
        $data = [
            'user_id' => 1,
            'career_id' => 2,
            'n_control' => '19-2011-34',
            'semestre' => 5,
            'group' => 'B+',
            'workshop' => 'Taller_especial/2024',
        ];

        // Act
        $result = StudentDetailMapper::toCreateStudentDetailDTO($data);

        // Assert
        $this->assertEquals('19-2011-34', $result->n_control);
        $this->assertEquals('B+', $result->group);
        $this->assertEquals('Taller_especial/2024', $result->workshop);
    }

    #[Test]
    public function it_handles_high_semester_numbers(): void
    {
        // Arrange
        $data = [
            'user_id' => 1,
            'career_id' => 2,
            'n_control' => '19201134',
            'semestre' => 12,
            'group' => 'A',
            'workshop' => 'Taller de Programación',
        ];

        // Act
        $result = StudentDetailMapper::toCreateStudentDetailDTO($data);

        // Assert
        $this->assertEquals(12, $result->semestre);
    }

    #[Test]
    public function it_preserves_data_types_correctly(): void
    {
        // Arrange
        $data = [
            'user_id' => '1',
            'career_id' => '2',
            'n_control' => 19201134,
            'semestre' => '5',
            'group' => 'A',
            'workshop' => 'Taller de Programación',
        ];

        // Act
        $result = StudentDetailMapper::toCreateStudentDetailDTO($data);

        // Assert
        $this->assertEquals('1', $result->user_id);
        $this->assertEquals('2', $result->career_id);
        $this->assertEquals(19201134, $result->n_control);
        $this->assertEquals('5', $result->semestre);
        $this->assertEquals('A', $result->group);
    }

}
