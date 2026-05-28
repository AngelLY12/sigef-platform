<?php

namespace Tests\Unit\Infraestructure\Events;

use App\Events\PaymentConceptUpdatedRelations;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentConceptUpdatedRelationsTest extends TestCase
{
    #[Test]
    public function event_is_created_with_correct_properties(): void
    {
        // Arrange
        $newPaymentConceptId = 123;
        $oldPaymentConceptArray = [
            'id' => 123,
            'concept_name' => 'Old Concept',
            'applies_to' => 'ALL_STUDENTS',
        ];
        $dtoArray = [
            'id' => 123,
            'applies_to' => 'SPECIFIC_STUDENTS',
            'students' => [1, 2, 3],
            'replace_relations' => true,
        ];
        $appliesTo = 'SPECIFIC_STUDENTS';
        $oldRecipientIds = [1, 2, 3, 4, 5];

        // Act
        $event = new PaymentConceptUpdatedRelations(
            $newPaymentConceptId,
            $oldPaymentConceptArray,
            $dtoArray,
            $appliesTo,
            $oldRecipientIds
        );

        // Assert
        $this->assertEquals($newPaymentConceptId, $event->newPaymentConceptId);
        $this->assertEquals($oldPaymentConceptArray, $event->oldPaymentConceptArray);
        $this->assertEquals($dtoArray, $event->dtoArray);
        $this->assertEquals($appliesTo, $event->appliesTo);
        $this->assertEquals($oldRecipientIds, $event->oldRecipientIds);
    }

    #[Test]
    public function event_handles_empty_arrays(): void
    {
        // Arrange
        $newPaymentConceptId = 456;
        $oldPaymentConceptArray = [];
        $dtoArray = [];
        $appliesTo = 'ALL_STUDENTS';
        $oldRecipientIds = [];

        // Act
        $event = new PaymentConceptUpdatedRelations(
            $newPaymentConceptId,
            $oldPaymentConceptArray,
            $dtoArray,
            $appliesTo,
            $oldRecipientIds
        );

        // Assert
        $this->assertEquals($newPaymentConceptId, $event->newPaymentConceptId);
        $this->assertEmpty($event->oldPaymentConceptArray);
        $this->assertEmpty($event->dtoArray);
        $this->assertEquals($appliesTo, $event->appliesTo);
        $this->assertEmpty($event->oldRecipientIds);
    }

    #[Test]
    public function event_handles_complex_dto_array_structure(): void
    {
        // Arrange
        $newPaymentConceptId = 789;
        $oldPaymentConceptArray = [
            'id' => 789,
            'concept_name' => 'Test Concept',
            'applies_to' => 'ALL_STUDENTS',
            'user_ids' => [],
            'career_ids' => [],
            'semesters' => [],
        ];
        $dtoArray = [
            'id' => 789,
            'applies_to' => 'BY_CAREER',
            'careers' => [1, 2],
            'semesters' => ['2024-1', '2024-2'],
            'replace_relations' => true,
            'exception_students' => [10, 11],
            'applicant_tags' => ['NEW_STUDENT', 'INTERNATIONAL'],
        ];
        $appliesTo = 'BY_CAREER';
        $oldRecipientIds = [100, 101, 102];

        // Act
        $event = new PaymentConceptUpdatedRelations(
            $newPaymentConceptId,
            $oldPaymentConceptArray,
            $dtoArray,
            $appliesTo,
            $oldRecipientIds
        );

        // Assert
        $this->assertEquals($newPaymentConceptId, $event->newPaymentConceptId);
        $this->assertArrayHasKey('applies_to', $event->oldPaymentConceptArray);
        $this->assertArrayHasKey('applies_to', $event->dtoArray);
        $this->assertArrayHasKey('careers', $event->dtoArray);
        $this->assertArrayHasKey('semesters', $event->dtoArray);
        $this->assertEquals($appliesTo, $event->appliesTo);
        $this->assertCount(3, $event->oldRecipientIds);
    }

    #[Test]
    public function event_handles_different_applies_to_values(): void
    {
        $testCases = [
            [
                'appliesTo' => 'ALL_STUDENTS',
                'dtoArray' => ['applies_to' => 'ALL_STUDENTS'],
                'oldRecipientIds' => [],
            ],
            [
                'appliesTo' => 'SPECIFIC_STUDENTS',
                'dtoArray' => ['applies_to' => 'SPECIFIC_STUDENTS', 'students' => [1, 2, 3]],
                'oldRecipientIds' => [1, 2, 3, 4, 5],
            ],
            [
                'appliesTo' => 'BY_CAREER',
                'dtoArray' => ['applies_to' => 'BY_CAREER', 'careers' => [1, 2]],
                'oldRecipientIds' => [100, 200, 300],
            ],
            [
                'appliesTo' => 'BY_TAG',
                'dtoArray' => ['applies_to' => 'BY_TAG', 'applicant_tags' => ['NEW', 'RETURNING']],
                'oldRecipientIds' => [50, 51, 52],
            ],
        ];

        foreach ($testCases as $index => $testCase) {
            $event = new PaymentConceptUpdatedRelations(
                100 + $index,
                ['id' => 100 + $index, 'applies_to' => 'OLD_VALUE'],
                $testCase['dtoArray'],
                $testCase['appliesTo'],
                $testCase['oldRecipientIds']
            );

            $this->assertEquals($testCase['appliesTo'], $event->appliesTo);
            $this->assertEquals($testCase['dtoArray'], $event->dtoArray);
            $this->assertEquals($testCase['oldRecipientIds'], $event->oldRecipientIds);
        }
    }

    #[Test]
    public function event_can_be_used_in_real_update_scenario(): void
    {
        // Simular escenario real: actualizar relaciones de un concepto de pago
        $newPaymentConceptId = 555;

        // Estado anterior del concepto
        $oldPaymentConceptArray = [
            'id' => 555,
            'concept_name' => 'Matrícula Semestral',
            'applies_to' => 'ALL_STUDENTS',
            'user_ids' => [],
            'career_ids' => [],
            'semesters' => [],
            'exception_user_ids' => [],
            'applicant_tags' => [],
        ];

        // DTO con los cambios solicitados
        $dtoArray = [
            'id' => 555,
            'applies_to' => 'BY_CAREER',
            'careers' => [1, 2, 3], // Ingeniería, Medicina, Derecho
            'semesters' => ['2024-1', '2024-2'],
            'replace_relations' => true,
            'exception_students' => [999, 888], // Estudiantes becados
            'replace_exceptions' => true,
            'applicant_tags' => ['NEW_STUDENT', 'REGULAR'],
        ];

        $appliesTo = 'BY_CAREER';

        // IDs de estudiantes que recibían el concepto antes
        $oldRecipientIds = range(1, 1000); // Todos los estudiantes

        // Act
        $event = new PaymentConceptUpdatedRelations(
            $newPaymentConceptId,
            $oldPaymentConceptArray,
            $dtoArray,
            $appliesTo,
            $oldRecipientIds
        );

        // Assert
        $this->assertEquals(555, $event->newPaymentConceptId);
        $this->assertEquals('ALL_STUDENTS', $event->oldPaymentConceptArray['applies_to']);
        $this->assertEquals('BY_CAREER', $event->dtoArray['applies_to']);
        $this->assertEquals('BY_CAREER', $event->appliesTo);
        $this->assertCount(1000, $event->oldRecipientIds);
        $this->assertContains(999, $event->dtoArray['exception_students']);
    }

    #[Test]
    public function event_serialization_preserves_all_data(): void
    {
        // Arrange
        $newPaymentConceptId = 777;
        $oldPaymentConceptArray = ['id' => 777, 'name' => 'Test'];
        $dtoArray = ['id' => 777, 'applies_to' => 'TEST'];
        $appliesTo = 'TEST';
        $oldRecipientIds = [1, 2, 3];

        $event = new PaymentConceptUpdatedRelations(
            $newPaymentConceptId,
            $oldPaymentConceptArray,
            $dtoArray,
            $appliesTo,
            $oldRecipientIds
        );

        // Act
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        // Assert
        $this->assertInstanceOf(PaymentConceptUpdatedRelations::class, $unserialized);
        $this->assertEquals($event->newPaymentConceptId, $unserialized->newPaymentConceptId);
        $this->assertEquals($event->oldPaymentConceptArray, $unserialized->oldPaymentConceptArray);
        $this->assertEquals($event->dtoArray, $unserialized->dtoArray);
        $this->assertEquals($event->appliesTo, $unserialized->appliesTo);
        $this->assertEquals($event->oldRecipientIds, $unserialized->oldRecipientIds);
    }

    #[Test]
    public function event_handles_large_data_sets(): void
    {
        // Arrange - Datos grandes pero realistas
        $newPaymentConceptId = 1000;

        // Array grande pero realista para un concepto
        $oldPaymentConceptArray = [
            'id' => 1000,
            'concept_name' => 'Large Concept',
            'applies_to' => 'SPECIFIC_STUDENTS',
            'user_ids' => range(1, 5000), // 5000 estudiantes específicos
            'career_ids' => [],
            'semesters' => ['2024-1', '2024-2', '2023-1', '2023-2'],
            'exception_user_ids' => range(5001, 5100), // 100 excepciones
            'applicant_tags' => ['REGULAR', 'NEW', 'INTERNATIONAL', 'SCHOLARSHIP'],
        ];

        $dtoArray = [
            'id' => 1000,
            'applies_to' => 'SPECIFIC_STUDENTS',
            'students' => range(1, 6000), // Ahora 6000 estudiantes
            'replace_relations' => true,
            'exception_students' => range(6001, 6100), // 100 excepciones nuevas
            'replace_exceptions' => true,
            'applicant_tags' => ['REGULAR', 'NEW', 'INTERNATIONAL'],
        ];

        $appliesTo = 'SPECIFIC_STUDENTS';
        $oldRecipientIds = range(1, 5000); // 5000 IDs antiguos

        // Act
        $event = new PaymentConceptUpdatedRelations(
            $newPaymentConceptId,
            $oldPaymentConceptArray,
            $dtoArray,
            $appliesTo,
            $oldRecipientIds
        );

        // Assert
        $this->assertEquals(1000, $event->newPaymentConceptId);
        $this->assertCount(5000, $event->oldPaymentConceptArray['user_ids']);
        $this->assertCount(6000, $event->dtoArray['students']);
        $this->assertCount(5000, $event->oldRecipientIds);
        $this->assertEquals('SPECIFIC_STUDENTS', $event->appliesTo);
    }

}
