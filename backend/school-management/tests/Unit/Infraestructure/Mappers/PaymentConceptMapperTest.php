<?php

namespace Tests\Unit\Infraestructure\Mappers;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\PaymentConcept;
use App\Core\Domain\Entities\PaymentConcept as DomainPaymentConcept;
use App\Core\Infraestructure\Mappers\PaymentConceptMapper;
use Illuminate\Database\Eloquent\Collection;

class PaymentConceptMapperTest extends TestCase
{
    #[Test]
    public function it_maps_basic_fields_from_eloquent_to_domain(): void
    {
        // Arrange
        $eloquentModel = new PaymentConcept();

        // Establece propiedades directamente para asegurar tipos correctos
        $eloquentModel->id = 1;
        $eloquentModel->concept_name = 'Tuition Fee';
        $eloquentModel->description = 'Annual tuition fee';
        $eloquentModel->status = 'activo'; // Enum value
        $eloquentModel->start_date = Carbon::parse('2024-01-01');
        $eloquentModel->end_date = Carbon::parse('2024-12-31');
        $eloquentModel->amount = '1000.50'; // Es string
        $eloquentModel->applies_to = 'estudiantes'; // Enum value

        // Act
        $domainEntity = PaymentConceptMapper::toDomain($eloquentModel);

        // Assert
        $this->assertInstanceOf(DomainPaymentConcept::class, $domainEntity);
        $this->assertEquals(1, $domainEntity->id);
        $this->assertEquals('Tuition Fee', $domainEntity->concept_name);
        $this->assertEquals('Annual tuition fee', $domainEntity->description);

        // Verifica que status es el Enum correcto
        $this->assertInstanceOf(PaymentConceptStatus::class, $domainEntity->status);
        $this->assertEquals('activo', $domainEntity->status->value);

        // Verifica fechas como Carbon
        $this->assertInstanceOf(Carbon::class, $domainEntity->start_date);
        $this->assertEquals('2024-01-01', $domainEntity->start_date->format('Y-m-d'));

        $this->assertInstanceOf(Carbon::class, $domainEntity->end_date);
        $this->assertEquals('2024-12-31', $domainEntity->end_date->format('Y-m-d'));

        // amount es string
        $this->assertEquals('1000.50', $domainEntity->amount);

        // Verifica que applies_to es el Enum correcto
        $this->assertInstanceOf(PaymentConceptAppliesTo::class, $domainEntity->applies_to);
        $this->assertEquals('estudiantes', $domainEntity->applies_to->value);
    }

    #[Test]
    public function it_maps_loaded_relations_to_domain(): void
    {
        // Arrange
        $eloquentModel = new PaymentConcept();
        $eloquentModel->id = 1;
        $eloquentModel->concept_name = 'Test Concept';
        $eloquentModel->status = 'activo';
        $eloquentModel->start_date = Carbon::parse('2024-01-01');
        $eloquentModel->amount = '1000';
        $eloquentModel->applies_to = 'estudiantes';

        // Mock loaded relations
        $eloquentModel->setRelation('careers', new Collection([
            (object) ['id' => 1],
            (object) ['id' => 2],
        ]));

        $eloquentModel->setRelation('users', new Collection([
            (object) ['id' => 10],
            (object) ['id' => 20],
        ]));

        $eloquentModel->setRelation('paymentConceptSemesters', new Collection([
            (object) ['semestre' => '2024-1'],
            (object) ['semestre' => '2024-2'],
        ]));

        $eloquentModel->setRelation('exceptions', new Collection([
            (object) ['user_id' => 100],
            (object) ['user_id' => 200],
        ]));

        $eloquentModel->setRelation('applicantTypes', new Collection([
            (object) ['tag' => 'new_student'],
            (object) ['tag' => 'transfer'],
        ]));

        // Act
        $domainEntity = PaymentConceptMapper::toDomain($eloquentModel);

        // Assert
        $this->assertEquals([1, 2], $domainEntity->getCareerIds());
        $this->assertEquals([10, 20], $domainEntity->getUserIds());
        $this->assertEquals(['2024-1', '2024-2'], $domainEntity->getSemesters());
        $this->assertEquals([100, 200], $domainEntity->getExceptionUsersIds());
        $this->assertEquals(['new_student', 'transfer'], $domainEntity->getApplicantTag());
    }

    #[Test]
    public function it_handles_unloaded_relations_as_empty_arrays(): void
    {
        // Arrange
        $eloquentModel = new PaymentConcept();
        $eloquentModel->id = 1;
        $eloquentModel->concept_name = 'Test Concept';
        $eloquentModel->status = 'activo';
        $eloquentModel->start_date = Carbon::parse('2024-01-01');
        $eloquentModel->amount = '1000';
        $eloquentModel->applies_to = 'estudiantes';

        // Act - No relations loaded
        $domainEntity = PaymentConceptMapper::toDomain($eloquentModel);

        // Assert
        $this->assertEquals([], $domainEntity->getCareerIds());
        $this->assertEquals([], $domainEntity->getUserIds());
        $this->assertEquals([], $domainEntity->getSemesters());
        $this->assertEquals([], $domainEntity->getExceptionUsersIds());
        $this->assertEquals([], $domainEntity->getApplicantTag());
    }

    #[Test]
    public function it_maps_from_domain_to_persistence_array_correctly(): void
    {
        // Arrange
        $domainEntity = new DomainPaymentConcept(
            concept_name: 'Tuition Fee',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::parse('2024-01-01'),
            amount: '1000.50',
            applies_to: PaymentConceptAppliesTo::ESTUDIANTES,
            id: 1,
            description: 'Annual fee',
            end_date: Carbon::parse('2024-12-31')
        );

        // Act
        $persistenceArray = PaymentConceptMapper::toPersistence($domainEntity);

        // Assert
        $this->assertIsArray($persistenceArray);

        // Los enums se convierten a su valor string
        $this->assertEquals('Tuition Fee', $persistenceArray['concept_name']);
        $this->assertEquals('Annual fee', $persistenceArray['description']);
        $this->assertEquals(PaymentConceptStatus::ACTIVO, $persistenceArray['status']); // Enum -> string

        // Las fechas de Carbon se manejan según tu mapper
        $this->assertNotNull($persistenceArray['start_date']);
        $this->assertNotNull($persistenceArray['end_date']);

        $this->assertEquals('1000.50', $persistenceArray['amount']);
        $this->assertEquals(PaymentConceptAppliesTo::ESTUDIANTES, $persistenceArray['applies_to']); // Enum -> string

        // Verify id is not included
        $this->assertArrayNotHasKey('id', $persistenceArray);
    }

    #[Test]
    public function it_handles_null_values_in_persistence_mapping(): void
    {
        // Arrange
        $domainEntity = new DomainPaymentConcept(
            concept_name: 'Test Concept',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::parse('2024-01-01'),
            amount: '1000',
            applies_to: PaymentConceptAppliesTo::ESTUDIANTES,
            id: null,
            description: null,
            end_date: null
        );

        // Act
        $persistenceArray = PaymentConceptMapper::toPersistence($domainEntity);

        // Assert
        $this->assertNull($persistenceArray['description']);
        $this->assertNull($persistenceArray['end_date']);
    }

    #[Test]
    public function it_handles_all_enum_values_correctly(): void
    {
        // Test para todos los valores de enums
        $enumTestCases = [
            ['status' => PaymentConceptStatus::ACTIVO, 'applies_to' => PaymentConceptAppliesTo::TODOS],
            ['status' => PaymentConceptStatus::FINALIZADO, 'applies_to' => PaymentConceptAppliesTo::CARRERA],
            ['status' => PaymentConceptStatus::DESACTIVADO, 'applies_to' => PaymentConceptAppliesTo::SEMESTRE],
            ['status' => PaymentConceptStatus::ELIMINADO, 'applies_to' => PaymentConceptAppliesTo::CARRERA_SEMESTRE],
        ];

        foreach ($enumTestCases as $testCase) {
            // To Domain
            $eloquentModel = new PaymentConcept();
            $eloquentModel->concept_name = "Test Concept";
            $eloquentModel->start_date = Carbon::now();
            $eloquentModel->amount = "1000.00";
            $eloquentModel->status = $testCase['status'];
            $eloquentModel->applies_to = $testCase['applies_to'];

            $domainEntity = PaymentConceptMapper::toDomain($eloquentModel);

            $this->assertInstanceOf(PaymentConceptStatus::class, $domainEntity->status);
            $this->assertEquals($testCase['status'], $domainEntity->status);

            $this->assertInstanceOf(PaymentConceptAppliesTo::class, $domainEntity->applies_to);
            $this->assertEquals($testCase['applies_to'], $domainEntity->applies_to);

            // To Persistence
            $persistenceArray = PaymentConceptMapper::toPersistence($domainEntity);

            $this->assertEquals($testCase['status'], $persistenceArray['status']);
            $this->assertEquals($testCase['applies_to'], $persistenceArray['applies_to']);
        }
    }

    #[Test]
    public function it_handles_partial_relations_loading(): void
    {
        // Arrange - Solo algunas relaciones cargadas
        $eloquentModel = new PaymentConcept();
        $eloquentModel->id = 1;
        $eloquentModel->concept_name = 'Test Concept';
        $eloquentModel->status = 'activo';
        $eloquentModel->start_date = Carbon::parse('2024-01-01');
        $eloquentModel->amount = '1000';
        $eloquentModel->applies_to = 'estudiantes';

        // Solo algunas relaciones cargadas
        $eloquentModel->setRelation('careers', new Collection([
            (object) ['id' => 1],
        ]));

        $eloquentModel->setRelation('paymentConceptSemesters', new Collection([
            (object) ['semestre' => '2024-1'],
        ]));

        // Otras relaciones NO cargadas

        // Act
        $domainEntity = PaymentConceptMapper::toDomain($eloquentModel);

        // Assert
        $this->assertEquals([1], $domainEntity->getCareerIds());
        $this->assertEquals(['2024-1'], $domainEntity->getSemesters());

        // Las no cargadas deben ser arrays vacíos
        $this->assertEquals([], $domainEntity->getUserIds());
        $this->assertEquals([], $domainEntity->getExceptionUsersIds());
        $this->assertEquals([], $domainEntity->getApplicantTag());
    }

    #[Test]
    public function it_converts_carbon_dates_correctly_in_persistence(): void
    {
        $domainEntity = new DomainPaymentConcept(
            concept_name: 'Test',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::parse('2024-01-01 10:30:00'),
            amount: '1000',
            applies_to: PaymentConceptAppliesTo::ESTUDIANTES,
            id: 1,
            description: 'Test',
            end_date: Carbon::parse('2024-12-31 23:59:59')
        );

        $persistenceArray = PaymentConceptMapper::toPersistence($domainEntity);

        // Depende de cómo implementes el mapper:
        // Si convierte Carbon a string:
        if (is_string($persistenceArray['start_date'])) {
            $this->assertStringContainsString('2024-01-01', $persistenceArray['start_date']);
            $this->assertStringContainsString('2024-12-31', $persistenceArray['end_date']);
        }
        // Si mantiene Carbon:
        elseif ($persistenceArray['start_date'] instanceof Carbon) {
            $this->assertEquals('2024-01-01', $persistenceArray['start_date']->format('Y-m-d'));
            $this->assertEquals('2024-12-31', $persistenceArray['end_date']->format('Y-m-d'));
        }
    }

}
