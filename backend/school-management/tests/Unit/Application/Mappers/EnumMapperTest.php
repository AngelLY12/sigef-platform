<?php

namespace Tests\Unit\Application\Mappers;

use App\Core\Application\Mappers\EnumMapper;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\User\UserBloodType;
use App\Core\Domain\Enum\User\UserGender;
use App\Core\Domain\Enum\User\UserStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnumMapperTest extends TestCase
{
    #[Test]
    public function from_stripe_maps_paid_status_correctly(): void
    {
        // Arrange
        $stripeStatuses = ['paid', 'no_payment_required'];

        foreach ($stripeStatuses as $stripeStatus) {
            // Act
            $result = EnumMapper::fromStripe($stripeStatus);

            // Assert
            $this->assertEquals(PaymentStatus::PAID, $result,
                "Stripe status '{$stripeStatus}' should map to PaymentStatus::PAID");
        }
    }

    #[Test]
    public function from_stripe_maps_unpaid_status_correctly(): void
    {
        // Act
        $result = EnumMapper::fromStripe('unpaid');

        // Assert
        $this->assertEquals(PaymentStatus::UNPAID, $result);
    }

    #[Test]
    public function from_stripe_maps_succeeded_status_correctly(): void
    {
        // Act
        $result = EnumMapper::fromStripe('succeeded');

        // Assert
        $this->assertEquals(PaymentStatus::SUCCEEDED, $result);
    }

    #[Test]
    public function from_stripe_maps_requires_action_status_correctly(): void
    {
        // Act
        $result = EnumMapper::fromStripe('requires_action');

        // Assert
        $this->assertEquals(PaymentStatus::REQUIRES_ACTION, $result);
    }

    #[Test]
    public function from_stripe_returns_default_for_unknown_status(): void
    {
        // Arrange
        $unknownStatuses = [
            'processing',
            'canceled',
            'refunded',
            'expired',
            'unknown_status',
            '',
            'invalid',
        ];

        foreach ($unknownStatuses as $unknownStatus) {
            // Act
            $result = EnumMapper::fromStripe($unknownStatus);

            // Assert
            $this->assertEquals(PaymentStatus::DEFAULT, $result,
                "Unknown Stripe status '{$unknownStatus}' should map to PaymentStatus::DEFAULT");
        }
    }

    #[Test]
    public function from_stripe_is_case_sensitive(): void
    {
        // Note: Stripe statuses are typically lowercase, but let's test case sensitivity
        $testCases = [
            ['input' => 'PAID', 'expected' => PaymentStatus::DEFAULT], // Uppercase
            ['input' => 'Paid', 'expected' => PaymentStatus::DEFAULT], // Capitalized
            ['input' => 'paid', 'expected' => PaymentStatus::PAID],    // Lowercase (correct)
        ];

        foreach ($testCases as $case) {
            $result = EnumMapper::fromStripe($case['input']);
            $this->assertEquals($case['expected'], $result,
                "Case sensitivity test failed for '{$case['input']}'");
        }
    }

    // ==================== TO PAYMENT CONCEPT APPLIES TO TESTS ====================

    #[Test]
    public function to_payment_concept_applies_to_maps_all_valid_values(): void
    {
        // Arrange
        $testCases = [
            ['input' => 'TODOS', 'expected' => PaymentConceptAppliesTo::TODOS],
            ['input' => 'ESTUDIANTES', 'expected' => PaymentConceptAppliesTo::ESTUDIANTES],
            ['input' => 'CARRERA', 'expected' => PaymentConceptAppliesTo::CARRERA],
            ['input' => 'SEMESTRE', 'expected' => PaymentConceptAppliesTo::SEMESTRE],
            ['input' => 'TAG', 'expected' => PaymentConceptAppliesTo::TAG],
        ];

        foreach ($testCases as $case) {
            // Act
            $result = EnumMapper::toPaymentConceptAppliesTo($case['input']);

            // Assert
            $this->assertEquals($case['expected'], $result,
                "Failed to map '{$case['input']}' to PaymentConceptAppliesTo");
        }
    }

    #[Test]
    public function to_payment_concept_applies_to_throws_exception_for_invalid_value(): void
    {
        // Arrange
        $invalidValues = [
            'INVALID_VALUE',
            'all_students', // wrong case
            'ALLSTUDENTS', // missing underscore
            '',
            'BY_CAREER_AND_SEMESTER', // not defined
        ];

        foreach ($invalidValues as $invalidValue) {
            // Expect
            $this->expectException(\ValueError::class);

            // Act
            EnumMapper::toPaymentConceptAppliesTo($invalidValue);
        }
    }

    // ==================== TO PAYMENT CONCEPT STATUS TESTS ====================

    #[Test]
    public function to_payment_concept_status_maps_all_valid_values(): void
    {
        // Arrange
        $testCases = [
            ['input' => 'ACTIVO', 'expected' => PaymentConceptStatus::ACTIVO],
            ['input' => 'FINALIZADO', 'expected' => PaymentConceptStatus::FINALIZADO],
            ['input' => 'ELIMINADO', 'expected' => PaymentConceptStatus::ELIMINADO],
            ['input' => 'DESACTIVADO', 'expected' => PaymentConceptStatus::DESACTIVADO],
        ];

        foreach ($testCases as $case) {
            // Act
            $result = EnumMapper::toPaymentConceptStatus($case['input']);

            // Assert
            $this->assertEquals($case['expected'], $result,
                "Failed to map '{$case['input']}' to PaymentConceptStatus");
        }
    }

    #[Test]
    public function to_payment_concept_status_throws_exception_for_invalid_value(): void
    {
        // Arrange
        $invalidValues = [
            'INVALID_STATUS',
            'activo', // wrong case
            'PENDING', // not defined
            '',
        ];

        foreach ($invalidValues as $invalidValue) {
            // Expect
            $this->expectException(\ValueError::class);

            // Act
            EnumMapper::toPaymentConceptStatus($invalidValue);
        }
    }

    // ==================== TO USER GENDER TESTS ====================

    #[Test]
    public function to_user_gender_maps_all_valid_values(): void
    {
        // Arrange
        $testCases = [
            ['input' => 'HOMBRE', 'expected' => UserGender::HOMBRE],
            ['input' => 'MUJER', 'expected' => UserGender::MUJER],
        ];

        foreach ($testCases as $case) {
            // Act
            $result = EnumMapper::toUserGender($case['input']);

            // Assert
            $this->assertEquals($case['expected'], $result,
                "Failed to map '{$case['input']}' to UserGender");
        }
    }

    #[Test]
    public function to_user_gender_throws_exception_for_invalid_value(): void
    {
        // Arrange
        $invalidValues = [
            'INVALID_GENDER',
            'male', // wrong case
            '',
            'M',
            'F',
        ];

        foreach ($invalidValues as $invalidValue) {
            // Expect
            $this->expectException(\ValueError::class);

            // Act
            EnumMapper::toUserGender($invalidValue);
        }
    }

    // ==================== TO USER BLOOD TYPE TESTS ====================

    #[Test]
    public function to_user_blood_type_maps_all_valid_values(): void
    {
        // Arrange
        $testCases = [
            ['input' => 'A+', 'expected' => UserBloodType::A_POSITIVE],
            ['input' => 'A-', 'expected' => UserBloodType::A_NEGATIVE],
            ['input' => 'B+', 'expected' => UserBloodType::B_POSITIVE],
            ['input' => 'B-', 'expected' => UserBloodType::B_NEGATIVE],
            ['input' => 'AB+', 'expected' => UserBloodType::AB_POSITIVE],
            ['input' => 'AB-', 'expected' => UserBloodType::AB_NEGATIVE],
            ['input' => 'O+', 'expected' => UserBloodType::O_POSITIVE],
            ['input' => 'O-', 'expected' => UserBloodType::O_NEGATIVE],
        ];

        foreach ($testCases as $case) {
            // Act
            $result = EnumMapper::toUserBloodType($case['input']);

            // Assert
            $this->assertEquals($case['expected'], $result,
                "Failed to map '{$case['input']}' to UserBloodType");
        }
    }

    #[Test]
    public function to_user_blood_type_throws_exception_for_invalid_value(): void
    {
        // Arrange
        $invalidValues = [
            'INVALID_BLOOD_TYPE',
            'a_positive', // wrong case
            'A_POSITIVO', // wrong format
            '',
            'A_POS',
        ];

        foreach ($invalidValues as $invalidValue) {
            // Expect
            $this->expectException(\ValueError::class);

            // Act
            EnumMapper::toUserBloodType($invalidValue);
        }
    }

    // ==================== TO USER STATUS TESTS ====================

    #[Test]
    public function to_user_status_maps_all_valid_values(): void
    {
        // Arrange
        $testCases = [
            ['input' => 'ACTIVO', 'expected' => UserStatus::ACTIVO],
            ['input' => 'BAJA-TEMPORAL', 'expected' => UserStatus::BAJA_TEMPORAL],
            ['input' => 'BAJA', 'expected' => UserStatus::BAJA],
            ['input' => 'ELIMINADO', 'expected' => UserStatus::ELIMINADO],
        ];

        foreach ($testCases as $case) {
            // Act
            $result = EnumMapper::toUserStatus($case['input']);

            // Assert
            $this->assertEquals($case['expected'], $result,
                "Failed to map '{$case['input']}' to UserStatus");
        }
    }

    #[Test]
    public function to_user_status_throws_exception_for_invalid_value(): void
    {
        // Arrange
        $invalidValues = [
            'INVALID_STATUS',
            'active', // wrong case
            '',
            'PENDING',
            'DELETED',
        ];

        foreach ($invalidValues as $invalidValue) {
            // Expect
            $this->expectException(\ValueError::class);

            // Act
            EnumMapper::toUserStatus($invalidValue);
        }
    }

    // ==================== EDGE CASE TESTS ====================
    #[Test]
    public function mapper_handles_null_or_empty_strings(): void
    {
        // Note: Estos tests dependen de si tus enums permiten valores vacíos
        // Si no los permiten, deberían lanzar excepción

        // Para fromStripe con string vacío
        $result = EnumMapper::fromStripe('');
        $this->assertEquals(PaymentStatus::DEFAULT, $result);

        // Para los otros métodos, deberían lanzar ValueError
        $methodsThatShouldThrow = [
            'toPaymentConceptAppliesTo',
            'toPaymentConceptStatus',
            'toUserGender',
            'toUserBloodType',
            'toUserStatus',
        ];

        foreach ($methodsThatShouldThrow as $method) {
            $this->expectException(\ValueError::class);
            EnumMapper::$method('');
        }
    }

    #[Test]
    public function from_stripe_with_whitespace(): void
    {
        // Arrange
        $testCases = [
            ['input' => ' paid ', 'expected' => PaymentStatus::DEFAULT], // Con espacios
            ['input' => "\tpaid\n", 'expected' => PaymentStatus::DEFAULT], // Con tabs y newlines
            ['input' => ' unpaid ', 'expected' => PaymentStatus::DEFAULT],
        ];

        foreach ($testCases as $case) {
            $result = EnumMapper::fromStripe($case['input']);
            $this->assertEquals($case['expected'], $result,
                "Stripe status with whitespace '{$case['input']}' should map to DEFAULT");
        }
    }

    // ==================== PERFORMANCE/INTEGRATION TESTS ====================

    #[Test]
    public function mapper_can_handle_multiple_calls_efficiently(): void
    {
        // Este test verifica que no hay problemas de performance con múltiples llamadas
        $iterations = 1000;

        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            EnumMapper::fromStripe('paid');
            EnumMapper::toPaymentConceptAppliesTo('TODOS');
            EnumMapper::toUserGender('HOMBRE');
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert que no toma más de 0.1 segundos para 1000 iteraciones
        // (esto es muy generoso, normalmente debería ser mucho más rápido)
        $this->assertLessThan(0.1, $executionTime,
            "Mapper should be efficient. Took {$executionTime}s for {$iterations} iterations");
    }

    #[Test]
    public function mapper_works_with_enum_backed_values(): void
    {
        // Este test verifica que los valores mapeados pueden usarse como strings
        $paymentStatus = EnumMapper::fromStripe('paid');
        $this->assertIsString($paymentStatus->value); // Si es un BackedEnum

        $userGender = EnumMapper::toUserGender('HOMBRE');
        $this->assertIsString($userGender->value); // Si es un BackedEnum
    }

    // ==================== COMPREHENSIVE COVERAGE TESTS ====================

    #[Test]
    public function comprehensive_from_stripe_coverage(): void
    {
        $coverageMatrix = [
            // Stripe status => Expected enum
            'paid' => PaymentStatus::PAID,
            'no_payment_required' => PaymentStatus::PAID,
            'unpaid' => PaymentStatus::UNPAID,
            'succeeded' => PaymentStatus::SUCCEEDED,
            'requires_action' => PaymentStatus::REQUIRES_ACTION,

            // Statuses de Stripe que NO están mapeados (deberían ser DEFAULT)
            'processing' => PaymentStatus::DEFAULT,
            'canceled' => PaymentStatus::DEFAULT,
            'requires_payment_method' => PaymentStatus::DEFAULT,
            'requires_capture' => PaymentStatus::DEFAULT,
            'requires_confirmation' => PaymentStatus::DEFAULT,
            'requires_customer_action' => PaymentStatus::DEFAULT,
            'incomplete' => PaymentStatus::DEFAULT,
            'incomplete_expired' => PaymentStatus::DEFAULT,
            'void' => PaymentStatus::DEFAULT,
        ];

        foreach ($coverageMatrix as $stripeStatus => $expectedEnum) {
            $result = EnumMapper::fromStripe($stripeStatus);
            $this->assertEquals($expectedEnum, $result,
                "Stripe status '{$stripeStatus}' should map to " . $expectedEnum->name);
        }
    }

    #[Test]
    public function mapper_methods_return_correct_enum_types(): void
    {
        // fromStripe
        $paymentStatus = EnumMapper::fromStripe('paid');
        $this->assertInstanceOf(PaymentStatus::class, $paymentStatus);

        // toPaymentConceptAppliesTo
        $appliesTo = EnumMapper::toPaymentConceptAppliesTo('TODOS');
        $this->assertInstanceOf(PaymentConceptAppliesTo::class, $appliesTo);

        // toPaymentConceptStatus
        $conceptStatus = EnumMapper::toPaymentConceptStatus('ACTIVO');
        $this->assertInstanceOf(PaymentConceptStatus::class, $conceptStatus);

        // toUserGender
        $userGender = EnumMapper::toUserGender('HOMBRE');
        $this->assertInstanceOf(UserGender::class, $userGender);

        // toUserBloodType
        $bloodType = EnumMapper::toUserBloodType('A+');
        $this->assertInstanceOf(UserBloodType::class, $bloodType);

        // toUserStatus
        $userStatus = EnumMapper::toUserStatus('ACTIVO');
        $this->assertInstanceOf(UserStatus::class, $userStatus);
    }

}
