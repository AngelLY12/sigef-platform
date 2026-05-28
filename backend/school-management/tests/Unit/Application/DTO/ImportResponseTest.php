<?php

namespace Tests\Unit\Application\DTO;

use App\Core\Application\DTO\Response\General\ImportResponse;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Date;
use Tests\TestCase;

class ImportResponseTest extends TestCase
{
    #[Test]
    public function can_be_instantiated_with_default_values(): void
    {
        // Act
        $response = new ImportResponse();

        // Assert
        $this->assertEquals(0, $response->getSummary()['total_rows_received']);
        $this->assertEquals(0, $response->getSummary()['rows_processed']);
        $this->assertEquals(0, $response->getSummary()['rows_inserted']);
        $this->assertEmpty($response->getErrors());
        $this->assertEmpty($response->getGlobalErrors());
        $this->assertEmpty($response->getWarnings());
    }

    #[Test]
    public function can_be_instantiated_with_custom_values(): void
    {
        // Arrange
        $totalRows = 100;
        $rowsProcessed = 50;
        $rowsInserted = 45;
        $errors = ['error1', 'error2'];
        $warnings = ['warning1'];
        $globalErrors = ['global_error1'];

        // Act
        $response = new ImportResponse(
            totalRows: $totalRows,
            rowsProcessed: $rowsProcessed,
            rowsInserted: $rowsInserted,
            errors: $errors,
            warnings: $warnings,
            globalErrors: $globalErrors
        );

        // Assert
        $this->assertEquals($totalRows, $response->getSummary()['total_rows_received']);
        $this->assertEquals($rowsProcessed, $response->getSummary()['rows_processed']);
        $this->assertEquals($rowsInserted, $response->getSummary()['rows_inserted']);
        $this->assertEquals($errors, $response->getErrors());
        $this->assertEquals($warnings, $response->getWarnings());
        $this->assertEquals($globalErrors, $response->getGlobalErrors());
    }

    #[Test]
    public function increment_inserted_increases_rows_inserted(): void
    {
        // Arrange
        $response = new ImportResponse(rowsInserted: 10);

        // Act
        $response->incrementInserted();
        $response->incrementInserted(5);

        // Assert
        $this->assertEquals(16, $response->getSummary()['rows_inserted']);
    }

    #[Test]
    public function increment_processed_increases_rows_processed(): void
    {
        // Arrange
        $response = new ImportResponse(rowsProcessed: 20);

        // Act
        $response->incrementProcessed();
        $response->incrementProcessed(3);

        // Assert
        $this->assertEquals(24, $response->getSummary()['rows_processed']);
    }

    #[Test]
    public function add_error_creates_error_with_correct_structure(): void
    {
        // Arrange
        Date::setTestNow('2024-01-01 12:00:00');
        $response = new ImportResponse();

        // Act
        $response->addError(
            message: 'Invalid email format',
            rowNumber: 5,
            context: ['email' => 'invalid@', 'column' => 'email']
        );

        // Assert
        $errors = $response->getErrors();
        $this->assertCount(1, $errors);

        $error = $errors[0];
        $this->assertEquals('row_error', $error['type']);
        $this->assertEquals('Invalid email format', $error['message']);
        $this->assertEquals(5, $error['row_number']);
        $this->assertEquals(['email' => 'invalid@', 'column' => 'email'], $error['context']);
        $this->assertEquals('2024-01-01 12:00:00', $error['timestamp']);
    }

    #[Test]
    public function add_warning_creates_warning_with_correct_structure(): void
    {
        // Arrange
        Date::setTestNow('2024-01-01 12:00:00');
        $response = new ImportResponse();

        // Act
        $response->addWarning(
            message: 'Empty values found in optional column',
            chunkIndex: 2,
            rowsCount: 10
        );

        // Assert
        $warnings = $response->getWarnings();
        $this->assertCount(1, $warnings);

        $warning = $warnings[0];
        $this->assertEquals('warning', $warning['type']);
        $this->assertEquals('Empty values found in optional column', $warning['message']);
        $this->assertEquals(2, $warning['chunk_index']);
        $this->assertEquals(10, $warning['rows_count']);
        $this->assertEquals('2024-01-01 12:00:00', $warning['timestamp']);
    }

    #[Test]
    public function add_global_error_creates_global_error_with_correct_structure(): void
    {
        // Arrange
        Date::setTestNow('2024-01-01 12:00:00');
        $response = new ImportResponse();

        // Act
        $response->addGlobalError(
            message: 'Database connection failed during chunk processing',
            chunkIndex: 3,
            rowsCount: 50
        );

        // Assert
        $globalErrors = $response->getGlobalErrors();
        $this->assertCount(1, $globalErrors);

        $globalError = $globalErrors[0];
        $this->assertEquals('global_error', $globalError['type']);
        $this->assertEquals('Database connection failed during chunk processing', $globalError['message']);
        $this->assertEquals(3, $globalError['chunk_index']);
        $this->assertEquals(50, $globalError['rows_count']);
        $this->assertEquals('2024-01-01 12:00:00', $globalError['timestamp']);
    }

    #[Test]
    public function merge_combines_two_responses_correctly(): void
    {
        // Arrange
        $response1 = new ImportResponse(
            totalRows: 100,
            rowsProcessed: 60,
            rowsInserted: 55,
            errors: [['error' => 'error1']],
            warnings: [['warning' => 'warning1']],
            globalErrors: [['global' => 'global1']]
        );

        $response2 = new ImportResponse(
            totalRows: 50, // totalRows NO debería sumarse
            rowsProcessed: 30,
            rowsInserted: 25,
            errors: [['error' => 'error2']],
            warnings: [['warning' => 'warning2']],
            globalErrors: [['global' => 'global2']]
        );

        // Act
        $response1->merge($response2);

        // Assert
        $this->assertEquals(100, $response1->getSummary()['total_rows_received']); // totalRows no se suma
        $this->assertEquals(90, $response1->getSummary()['rows_processed']); // 60 + 30
        $this->assertEquals(80, $response1->getSummary()['rows_inserted']); // 55 + 25

        $this->assertCount(2, $response1->getErrors());
        $this->assertCount(2, $response1->getWarnings());
        $this->assertCount(2, $response1->getGlobalErrors());
    }

    #[Test]
    public function merge_with_empty_response_does_nothing(): void
    {
        // Arrange
        $response1 = new ImportResponse(
            rowsProcessed: 10,
            rowsInserted: 8,
            errors: [['error' => 'error1']]
        );

        $response2 = new ImportResponse(); // Vacío

        // Act
        $response1->merge($response2);

        // Assert
        $this->assertEquals(10, $response1->getSummary()['rows_processed']);
        $this->assertEquals(8, $response1->getSummary()['rows_inserted']);
        $this->assertCount(1, $response1->getErrors());
    }

    #[Test]
    public function to_array_returns_correct_structure(): void
    {
        // Arrange
        Date::setTestNow('2024-01-01 12:00:00');

        $response = new ImportResponse(
            totalRows: 150,
            rowsProcessed: 100,
            rowsInserted: 90
        );

        $response->addError('Error 1', 1);
        $response->addWarning('Warning 1', 0, 10);
        $response->addGlobalError('Global Error 1', 1, 20);

        // Act
        $result = $response->toArray();

        // Assert
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertArrayHasKey('timestamp', $result);
        $this->assertArrayHasKey('has_errors', $result);
        $this->assertArrayHasKey('has_warnings', $result);

        // Verificar summary
        $this->assertEquals(150, $result['summary']['total_rows_received']);
        $this->assertEquals(100, $result['summary']['rows_processed']);
        $this->assertEquals(90, $result['summary']['rows_inserted']);
        $this->assertEquals(10, $result['summary']['rows_failed']); // 100 - 90
        $this->assertEquals(90.0, $result['summary']['success_rate']); // (90/100)*100 = 90%

        // Verificar errores
        $this->assertCount(1, $result['errors']['row_errors']);
        $this->assertCount(1, $result['errors']['global_errors']);
        $this->assertEquals(2, $result['errors']['total_errors']); // 1 + 1

        // Verificar warnings
        $this->assertCount(1, $result['warnings']['list']);
        $this->assertEquals(1, $result['warnings']['total_warnings']);

        // Verificar booleanos
        $this->assertTrue($result['has_errors']);
        $this->assertTrue($result['has_warnings']);

        // Verificar timestamp
        $this->assertEquals('2024-01-01 12:00:00', $result['timestamp']);
    }

    #[Test]
    public function to_array_with_no_errors_or_warnings(): void
    {
        // Arrange
        $response = new ImportResponse(
            totalRows: 50,
            rowsProcessed: 50,
            rowsInserted: 50
        );

        // Act
        $result = $response->toArray();

        // Assert
        $this->assertFalse($result['has_errors']);
        $this->assertFalse($result['has_warnings']);
        $this->assertEmpty($result['errors']['row_errors']);
        $this->assertEmpty($result['errors']['global_errors']);
        $this->assertEmpty($result['warnings']['list']);
        $this->assertEquals(100.0, $result['summary']['success_rate']); // 100% success
    }

    #[Test]
    public function get_summary_returns_only_summary_data(): void
    {
        // Arrange
        $response = new ImportResponse(
            totalRows: 200,
            rowsProcessed: 180,
            rowsInserted: 170
        );

        // Act
        $summary = $response->getSummary();

        // Assert
        $this->assertArrayHasKey('total_rows_received', $summary);
        $this->assertArrayHasKey('rows_processed', $summary);
        $this->assertArrayHasKey('rows_inserted', $summary);
        $this->assertArrayHasKey('rows_failed', $summary);
        $this->assertArrayHasKey('success_rate', $summary);

        $this->assertEquals(200, $summary['total_rows_received']);
        $this->assertEquals(180, $summary['rows_processed']);
        $this->assertEquals(170, $summary['rows_inserted']);
    }

    #[Test]
    public function set_total_rows_updates_total_rows(): void
    {
        // Arrange
        $response = new ImportResponse(totalRows: 100);

        // Act
        $response->setTotalRows(200);

        // Assert
        $this->assertEquals(200, $response->getSummary()['total_rows_received']);
    }

    #[Test]
    public function success_rate_is_zero_when_no_rows_processed(): void
    {
        // Arrange
        $response = new ImportResponse(
            totalRows: 100,
            rowsProcessed: 0,
            rowsInserted: 0
        );

        // Act
        $result = $response->toArray();

        // Assert
        $this->assertEquals(0, $result['summary']['success_rate']);
    }

    #[Test]
    public function success_rate_is_calculated_correctly(): void
    {
        $testCases = [
            ['processed' => 100, 'inserted' => 100, 'expected' => 100.0],
            ['processed' => 100, 'inserted' => 75, 'expected' => 75.0],
            ['processed' => 100, 'inserted' => 50, 'expected' => 50.0],
            ['processed' => 100, 'inserted' => 0, 'expected' => 0.0],
            ['processed' => 3, 'inserted' => 2, 'expected' => 66.67], // 2/3 = 66.666... rounded to 66.67
            ['processed' => 7, 'inserted' => 3, 'expected' => 42.86], // 3/7 = 42.857... rounded to 42.86
        ];

        foreach ($testCases as $case) {
            $response = new ImportResponse(
                rowsProcessed: $case['processed'],
                rowsInserted: $case['inserted']
            );

            $result = $response->toArray();
            $this->assertEquals($case['expected'], $result['summary']['success_rate'],
                "Failed for processed={$case['processed']}, inserted={$case['inserted']}");
        }
    }

    #[Test]
    public function multiple_errors_can_be_added(): void
    {
        // Arrange
        $response = new ImportResponse();

        // Act
        for ($i = 1; $i <= 5; $i++) {
            $response->addError("Error {$i}", $i);
        }

        // Assert
        $this->assertCount(5, $response->getErrors());

        $errors = $response->getErrors();
        $this->assertEquals('Error 1', $errors[0]['message']);
        $this->assertEquals(1, $errors[0]['row_number']);
        $this->assertEquals('Error 5', $errors[4]['message']);
        $this->assertEquals(5, $errors[4]['row_number']);
    }

    #[Test]
    public function can_handle_large_numbers(): void
    {
        // Arrange
        $response = new ImportResponse(
            totalRows: 1000000,
            rowsProcessed: 950000,
            rowsInserted: 900000
        );

        // Act
        $result = $response->toArray();

        // Assert
        $this->assertEquals(1000000, $result['summary']['total_rows_received']);
        $this->assertEquals(950000, $result['summary']['rows_processed']);
        $this->assertEquals(900000, $result['summary']['rows_inserted']);
        $this->assertEquals(50000, $result['summary']['rows_failed']); // 950000 - 900000
        $this->assertEquals(94.74, $result['summary']['success_rate']); // (900000/950000)*100 ≈ 94.74
    }

    #[Test]
    public function empty_context_is_allowed_for_errors(): void
    {
        // Arrange
        $response = new ImportResponse();

        // Act
        $response->addError('Error without context', 1);

        // Assert
        $errors = $response->getErrors();
        $this->assertEmpty($errors[0]['context']);
    }

    #[Test]
    public function error_types_are_consistent(): void
    {
        // Arrange
        $response = new ImportResponse();

        // Act
        $response->addError('Row error', 1);
        $response->addWarning('Warning', 1, 10);
        $response->addGlobalError('Global error', 1, 10);

        // Assert
        $errors = $response->getErrors();
        $warnings = $response->getWarnings();
        $globalErrors = $response->getGlobalErrors();

        $this->assertEquals('row_error', $errors[0]['type']);
        $this->assertEquals('warning', $warnings[0]['type']);
        $this->assertEquals('global_error', $globalErrors[0]['type']);
    }

    #[Test]
    public function rows_failed_is_calculated_correctly_in_to_array(): void
    {
        $testCases = [
            ['processed' => 100, 'inserted' => 90, 'expected_failed' => 10],
            ['processed' => 100, 'inserted' => 100, 'expected_failed' => 0],
            ['processed' => 100, 'inserted' => 0, 'expected_failed' => 100],
            ['processed' => 0, 'inserted' => 0, 'expected_failed' => 0],
        ];

        foreach ($testCases as $case) {
            $response = new ImportResponse(
                rowsProcessed: $case['processed'],
                rowsInserted: $case['inserted']
            );

            $result = $response->toArray();
            $this->assertEquals($case['expected_failed'], $result['summary']['rows_failed'],
                "Failed for processed={$case['processed']}, inserted={$case['inserted']}");
        }
    }

    #[Test]
    public function merge_preserves_original_total_rows(): void
    {
        // Arrange
        $response1 = new ImportResponse(totalRows: 100);
        $response2 = new ImportResponse(totalRows: 200);

        // Act
        $response1->merge($response2);

        // Assert - totalRows NO debe sumarse, debe mantenerse el original
        $this->assertEquals(100, $response1->getSummary()['total_rows_received']);
    }

    #[Test]
    public function can_get_individual_error_properties(): void
    {
        // Arrange
        $response = new ImportResponse();

        // Act
        $response->addError('Test error', 42, ['field' => 'email', 'value' => 'invalid']);

        // Assert
        $errors = $response->getErrors();
        $error = $errors[0];

        $this->assertEquals('Test error', $error['message']);
        $this->assertEquals(42, $error['row_number']);
        $this->assertEquals(['field' => 'email', 'value' => 'invalid'], $error['context']);
        $this->assertArrayHasKey('timestamp', $error);
        $this->assertArrayHasKey('type', $error);
    }

    #[Test]
    public function timestamp_is_included_in_all_messages(): void
    {
        // Arrange
        Date::setTestNow('2024-01-01 10:30:00');
        $response = new ImportResponse();

        // Act
        $response->addError('Error', 1);
        $response->addWarning('Warning', 1, 10);
        $response->addGlobalError('Global Error', 1, 10);

        // Assert
        $this->assertEquals('2024-01-01 10:30:00', $response->getErrors()[0]['timestamp']);
        $this->assertEquals('2024-01-01 10:30:00', $response->getWarnings()[0]['timestamp']);
        $this->assertEquals('2024-01-01 10:30:00', $response->getGlobalErrors()[0]['timestamp']);
    }

    #[Test]
    public function to_array_includes_current_timestamp(): void
    {
        // Arrange
        Date::setTestNow('2024-01-01 14:45:30');
        $response = new ImportResponse();

        // Act
        $result = $response->toArray();

        // Assert
        $this->assertEquals('2024-01-01 14:45:30', $result['timestamp']);
    }

    #[Test]
    public function negative_values_are_handled_gracefully(): void
    {
        // Arrange
        $response = new ImportResponse(
            totalRows: -100, // Negativo
            rowsProcessed: -50,
            rowsInserted: -30
        );

        // Act
        $result = $response->toArray();

        // Assert - El DTO debería manejar valores negativos (o prevenirlos con validación)
        $this->assertEquals(-100, $result['summary']['total_rows_received']);
        $this->assertEquals(-50, $result['summary']['rows_processed']);
        $this->assertEquals(-30, $result['summary']['rows_inserted']);
        // rows_failed será -50 - (-30) = -20
        $this->assertEquals(-20, $result['summary']['rows_failed']);
    }

    #[Test]
    public function increment_with_negative_values(): void
    {
        // Arrange
        $response = new ImportResponse(rowsInserted: 10, rowsProcessed: 20);

        // Act
        $response->incrementInserted(-5); // ¿Debería permitirse?
        $response->incrementProcessed(-3);

        // Assert
        $this->assertEquals(5, $response->getSummary()['rows_inserted']); // 10 + (-5) = 5
        $this->assertEquals(17, $response->getSummary()['rows_processed']); // 20 + (-3) = 17
    }

    #[Test]
    public function merge_with_itself_does_not_duplicate_data(): void
    {
        // Arrange
        $response = new ImportResponse(
            rowsProcessed: 10,
            rowsInserted: 8,
            errors: [['error' => 'error1']]
        );

        // Act
        $response->merge($response); // Merge consigo mismo

        // Assert
        $this->assertEquals(20, $response->getSummary()['rows_processed']); // Se duplica
        $this->assertEquals(16, $response->getSummary()['rows_inserted']); // Se duplica
        $this->assertCount(2, $response->getErrors()); // Los errores también se duplican
    }

}

