<?php

namespace Tests\Unit\Infraestructure\Mappers;

use PHPUnit\Framework\Attributes\Test;
use App\Models\StudentDetail;
use App\Core\Domain\Entities\StudentDetail as DomainStudentDetail;
use App\Core\Application\DTO\Request\StudentDetail\CreateStudentDetailDTO;
use App\Core\Infraestructure\Mappers\StudentDetailMapper;
use Tests\TestCase;

class StudentDetailMapperTest extends TestCase
{
    #[Test]
    public function it_maps_all_fields_from_eloquent_to_domain(): void
    {
        // Arrange
        $eloquentModel = new StudentDetail();

        // Establece propiedades directamente
        $eloquentModel->id = 1;
        $eloquentModel->user_id = 100;
        $eloquentModel->career_id = 5;
        $eloquentModel->n_control = '20240001';
        $eloquentModel->semestre = 3;
        $eloquentModel->group = 'A';
        $eloquentModel->workshop = 'Taller de Programación';

        // Act
        $domainEntity = StudentDetailMapper::toDomain($eloquentModel);

        // Assert
        $this->assertInstanceOf(DomainStudentDetail::class, $domainEntity);

        // Campos obligatorios
        $this->assertEquals(100, $domainEntity->user_id);
        $this->assertEquals(1, $domainEntity->id);

        // Campos opcionales con valores
        $this->assertEquals(5, $domainEntity->career_id);
        $this->assertEquals('20240001', $domainEntity->n_control);
        $this->assertEquals(3, $domainEntity->semestre);
        $this->assertEquals('A', $domainEntity->group);
        $this->assertEquals('Taller de Programación', $domainEntity->workshop);
    }

    #[Test]
    public function it_handles_null_values_for_optional_fields_in_to_domain(): void
    {
        // Arrange
        $eloquentModel = new StudentDetail();
        $eloquentModel->user_id = 200;
        $eloquentModel->id = null;
        $eloquentModel->career_id = null;
        $eloquentModel->n_control = null;
        $eloquentModel->semestre = null;
        $eloquentModel->group = null;
        $eloquentModel->workshop = null;

        // Act
        $domainEntity = StudentDetailMapper::toDomain($eloquentModel);

        // Assert
        $this->assertEquals(200, $domainEntity->user_id);

        // Campos opcionales nulos
        $this->assertNull($domainEntity->id);
        $this->assertNull($domainEntity->career_id);
        $this->assertNull($domainEntity->n_control);
        $this->assertNull($domainEntity->semestre);
        $this->assertNull($domainEntity->group);
        $this->assertNull($domainEntity->workshop);
    }

    #[Test]
    public function it_handles_partial_data_in_to_domain(): void
    {
        // Arrange - solo algunos campos tienen valores
        $eloquentModel = new StudentDetail();
        $eloquentModel->id = 10;
        $eloquentModel->user_id = 300;
        $eloquentModel->career_id = 8;
        $eloquentModel->n_control = '20240002';
        // semestre, group, workshop son null

        // Act
        $domainEntity = StudentDetailMapper::toDomain($eloquentModel);

        // Assert
        $this->assertEquals(300, $domainEntity->user_id);
        $this->assertEquals(10, $domainEntity->id);
        $this->assertEquals(8, $domainEntity->career_id);
        $this->assertEquals('20240002', $domainEntity->n_control);
        $this->assertNull($domainEntity->semestre);
        $this->assertNull($domainEntity->group);
        $this->assertNull($domainEntity->workshop);
    }

    #[Test]
    public function it_maps_from_dto_to_persistence_array_correctly(): void
    {
        // Arrange - Crear DTO con datos completos
        $dto = new CreateStudentDetailDTO(
            user_id: 150,
            career_id: 3,
            n_control: '20240003',
            semestre: 5,
            group: 'B',
            workshop: 'Taller de Bases de Datos'
        );

        // Act
        $persistenceArray = StudentDetailMapper::toPersistence($dto);

        // Assert
        $this->assertIsArray($persistenceArray);

        // Campos obligatorios
        $this->assertEquals(150, $persistenceArray['user_id']);

        // Campos opcionales
        $this->assertEquals(3, $persistenceArray['career_id']);
        $this->assertEquals('20240003', $persistenceArray['n_control']);
        $this->assertEquals(5, $persistenceArray['semestre']);
        $this->assertEquals('B', $persistenceArray['group']);
        $this->assertEquals('Taller de Bases de Datos', $persistenceArray['workshop']);

        // Campos que NO deben estar
        $this->assertArrayNotHasKey('id', $persistenceArray);
    }

    #[Test]
    public function it_handles_null_values_in_dto_to_persistence(): void
    {
        // Arrange - DTO con valores nulos
        $dto = new CreateStudentDetailDTO(
            user_id: 250,
            career_id: null,
            n_control: null,
            semestre: null,
            group: null,
            workshop: null
        );

        // Act
        $persistenceArray = StudentDetailMapper::toPersistence($dto);

        // Assert
        $this->assertEquals(250, $persistenceArray['user_id']);

        // Campos opcionales nulos
        $this->assertNull($persistenceArray['career_id']);
        $this->assertNull($persistenceArray['n_control']);
        $this->assertNull($persistenceArray['semestre']);
        $this->assertNull($persistenceArray['group']);
        $this->assertNull($persistenceArray['workshop']);
    }

    #[Test]
    public function it_handles_empty_strings_in_dto_to_persistence(): void
    {
        // Arrange - DTO con strings vacíos
        $dto = new CreateStudentDetailDTO(
            user_id: 350,
            career_id: null,
            n_control: '',
            semestre: null,
            group: '',
            workshop: ''
        );

        // Act
        $persistenceArray = StudentDetailMapper::toPersistence($dto);

        // Assert
        $this->assertEquals(350, $persistenceArray['user_id']);
        $this->assertEquals('', $persistenceArray['n_control']);
        $this->assertEquals('', $persistenceArray['group']);
        $this->assertEquals('', $persistenceArray['workshop']);
    }

    #[Test]
    public function it_maps_different_semester_values(): void
    {
        $semesterTestCases = [
            1,
            2,
            3,
            4,
            5,
            6,
            7,
            8,
            9,
            10,
            null, // También prueba null
        ];

        foreach ($semesterTestCases as $semester) {
            // To Domain
            $eloquentModel = new StudentDetail();
            $eloquentModel->user_id = 100;
            $eloquentModel->id = 1;
            $eloquentModel->semestre = $semester;

            $domainEntity = StudentDetailMapper::toDomain($eloquentModel);

            $this->assertEquals($semester, $domainEntity->semestre);

            // To Persistence (si tenemos DTO)
            if ($semester !== null) {
                $dto = new CreateStudentDetailDTO(
                    user_id: 100,
                    career_id: 1,
                    n_control: '20240001',
                    semestre: $semester,
                    group: 'A',
                    workshop: 'Taller'
                );

                $persistenceArray = StudentDetailMapper::toPersistence($dto);
                $this->assertEquals($semester, $persistenceArray['semestre']);
            }
        }
    }

    #[Test]
    public function it_maps_different_n_control_formats(): void
    {
        $nControlTestCases = [
            '20240001',
            '2024-0001',
            '24-001',
            'A20240001',
            '2024ABC001',
            null,
            '',
        ];

        foreach ($nControlTestCases as $nControl) {
            // To Domain
            $eloquentModel = new StudentDetail();
            $eloquentModel->user_id = 100;
            $eloquentModel->id = 1;
            $eloquentModel->n_control = $nControl;

            $domainEntity = StudentDetailMapper::toDomain($eloquentModel);

            $this->assertEquals($nControl, $domainEntity->n_control);
        }
    }

    #[Test]
    public function it_maps_different_group_values(): void
    {
        $groupTestCases = [
            'A',
            'B',
            'C',
            'D',
            'A1',
            'B2',
            'MATUTINO',
            'VESPERTINO',
            null,
            '',
        ];

        foreach ($groupTestCases as $group) {
            // To Domain
            $eloquentModel = new StudentDetail();
            $eloquentModel->user_id = 100;
            $eloquentModel->id = 1;
            $eloquentModel->group = $group;

            $domainEntity = StudentDetailMapper::toDomain($eloquentModel);

            $this->assertEquals($group, $domainEntity->group);
        }
    }

    #[Test]
    public function it_maps_different_workshop_values(): void
    {
        $workshopTestCases = [
            'Taller de Programación',
            'Taller de Bases de Datos',
            'Taller de Redes',
            'Laboratorio de Física',
            'Laboratorio de Química',
            'Seminario de Investigación',
            null,
            '',
        ];

        foreach ($workshopTestCases as $workshop) {
            // To Domain
            $eloquentModel = new StudentDetail();
            $eloquentModel->user_id = 100;
            $eloquentModel->id = 1;
            $eloquentModel->workshop = $workshop;

            $domainEntity = StudentDetailMapper::toDomain($eloquentModel);

            $this->assertEquals($workshop, $domainEntity->workshop);
        }
    }

    #[Test]
    public function it_excludes_id_from_persistence_array(): void
    {
        // Verifica que id nunca esté en el array de persistencia
        $dto = new CreateStudentDetailDTO(
            user_id: 400,
            career_id: 2,
            n_control: '20240004',
            semestre: 4,
            group: 'C',
            workshop: 'Taller'
        );

        $persistenceArray = StudentDetailMapper::toPersistence($dto);

        $this->assertArrayNotHasKey('id', $persistenceArray);
    }

    #[Test]
    public function it_handles_large_career_ids(): void
    {
        $largeIds = [
            1,
            10,
            100,
            1000,
            9999,
            null,
        ];

        foreach ($largeIds as $careerId) {
            // To Domain
            $eloquentModel = new StudentDetail();
            $eloquentModel->user_id = 100;
            $eloquentModel->id = 1;
            $eloquentModel->career_id = $careerId;

            $domainEntity = StudentDetailMapper::toDomain($eloquentModel);

            $this->assertEquals($careerId, $domainEntity->career_id);

            // To Persistence
            if ($careerId !== null) {
                $dto = new CreateStudentDetailDTO(
                    user_id: 100,
                    career_id: $careerId,
                    n_control: '20240001',
                    semestre: 1,
                    group: 'A',
                    workshop: 'Taller'
                );

                $persistenceArray = StudentDetailMapper::toPersistence($dto);
                $this->assertEquals($careerId, $persistenceArray['career_id']);
            }
        }
    }

    #[Test]
    public function it_preserves_user_id_as_required_field(): void
    {
        // user_id es el único campo realmente requerido

        // To Domain - debe tener user_id
        $eloquentModel = new StudentDetail();
        $eloquentModel->user_id = 999;
        $eloquentModel->id = null;
        // Todos los demás campos son null

        $domainEntity = StudentDetailMapper::toDomain($eloquentModel);
        $this->assertEquals(999, $domainEntity->user_id);

        // To Persistence - DTO debe tener user_id
        $dto = new CreateStudentDetailDTO(
            user_id: 888,
            career_id: null,
            n_control: null,
            semestre: null,
            group: null,
            workshop: null
        );

        $persistenceArray = StudentDetailMapper::toPersistence($dto);
        $this->assertEquals(888, $persistenceArray['user_id']);
    }

    #[Test]
    public function it_creates_domain_entity_with_promote_method(): void
    {
        // Arrange
        $eloquentModel = new StudentDetail();
        $eloquentModel->id = 1;
        $eloquentModel->user_id = 100;
        $eloquentModel->semestre = 3;

        // Act
        $domainEntity = StudentDetailMapper::toDomain($eloquentModel);

        // Assert - Verifica que la entidad tiene el método promote()
        $this->assertTrue(method_exists($domainEntity, 'promote'));

        // Test del método promote
        $initialSemester = $domainEntity->semestre;
        $domainEntity->promote();

        if ($initialSemester !== null) {
            $this->assertEquals($initialSemester + 1, $domainEntity->semestre);
        } else {
            $this->assertNull($domainEntity->semestre);
        }
    }

    #[Test]
    public function it_creates_domain_entity_with_to_array_method(): void
    {
        // Arrange
        $eloquentModel = new StudentDetail();
        $eloquentModel->id = 1;
        $eloquentModel->user_id = 100;
        $eloquentModel->career_id = 5;
        $eloquentModel->n_control = '20240001';
        $eloquentModel->semestre = 3;
        $eloquentModel->group = 'A';
        $eloquentModel->workshop = 'Taller';

        // Act
        $domainEntity = StudentDetailMapper::toDomain($eloquentModel);

        // Assert - Verifica que la entidad tiene el método toArray()
        $this->assertTrue(method_exists($domainEntity, 'toArray'));

        // Test del método toArray
        $array = $domainEntity->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(100, $array['user_id']);
        $this->assertEquals(5, $array['career_id']);
        $this->assertEquals('20240001', $array['n_control']);
        $this->assertEquals(3, $array['semestre']);
        $this->assertEquals('A', $array['group']);
        $this->assertEquals('Taller', $array['workshop']);

        // toArray() no debe incluir el id
        $this->assertArrayNotHasKey('id', $array);
    }

    #[Test]
    public function it_maps_special_characters_in_string_fields(): void
    {
        $specialCases = [
            'n_control' => ['2024-001', '2024/001', '2024_001', '2024#001', '2024@001'],
            'group' => ['A-1', 'B/2', 'C_3', 'D#4', 'E@5'],
            'workshop' => ['Taller I', 'Taller II', 'Taller-III', 'Taller/IV', 'Taller_V'],
        ];

        foreach ($specialCases['n_control'] as $nControl) {
            $eloquentModel = new StudentDetail();
            $eloquentModel->user_id = 100;
            $eloquentModel->id = 1;
            $eloquentModel->n_control = $nControl;

            $domainEntity = StudentDetailMapper::toDomain($eloquentModel);
            $this->assertEquals($nControl, $domainEntity->n_control);
        }

        foreach ($specialCases['group'] as $group) {
            $eloquentModel = new StudentDetail();
            $eloquentModel->user_id = 100;
            $eloquentModel->id = 1;
            $eloquentModel->group = $group;

            $domainEntity = StudentDetailMapper::toDomain($eloquentModel);
            $this->assertEquals($group, $domainEntity->group);
        }

        foreach ($specialCases['workshop'] as $workshop) {
            $eloquentModel = new StudentDetail();
            $eloquentModel->user_id = 100;
            $eloquentModel->id = 1;
            $eloquentModel->workshop = $workshop;

            $domainEntity = StudentDetailMapper::toDomain($eloquentModel);
            $this->assertEquals($workshop, $domainEntity->workshop);
        }
    }

    #[Test]
    public function it_handles_max_values_for_semester(): void
    {
        // Test para valores límite de semestre
        $semesterTestCases = [
            0,  // ¿Permitido? Depende de tu lógica de negocio
            1,  // Mínimo típico
            12, // Máximo típico para carreras largas
            20, // Valor extremo
            null,
        ];

        foreach ($semesterTestCases as $semester) {
            $eloquentModel = new StudentDetail();
            $eloquentModel->user_id = 100;
            $eloquentModel->id = 1;
            $eloquentModel->semestre = $semester;

            $domainEntity = StudentDetailMapper::toDomain($eloquentModel);

            // Solo verifica que el mapeo funcione, la validación de negocio
            // debería estar en otra parte (validators, value objects, etc.)
            $this->assertEquals($semester, $domainEntity->semestre);
        }
    }

}
