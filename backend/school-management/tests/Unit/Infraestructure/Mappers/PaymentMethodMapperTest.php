<?php

namespace Tests\Unit\Infraestructure\Mappers;

use App\Core\Infraestructure\Mappers\PaymentMethodMapper;
use App\Models\PaymentMethod;
use App\Core\Domain\Entities\PaymentMethod as DomainPaymentMethod;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentMethodMapperTest extends TestCase
{
    #[Test]
    public function it_maps_all_fields_from_eloquent_to_domain(): void
    {
        // Arrange
        $eloquentModel = new PaymentMethod();

        // Establece propiedades directamente
        $eloquentModel->id = 1;
        $eloquentModel->user_id = 100;
        $eloquentModel->stripe_payment_method_id = 'pm_123456789';
        $eloquentModel->brand = 'visa';
        $eloquentModel->last4 = '4242';
        $eloquentModel->exp_month = 12;
        $eloquentModel->exp_year = 2025;

        // Act
        $domainEntity = PaymentMethodMapper::toDomain($eloquentModel);

        // Assert
        $this->assertInstanceOf(DomainPaymentMethod::class, $domainEntity);

        // Campos obligatorios
        $this->assertEquals(100, $domainEntity->user_id);
        $this->assertEquals('pm_123456789', $domainEntity->stripe_payment_method_id);

        // Campos opcionales con valores
        $this->assertEquals('visa', $domainEntity->brand);
        $this->assertEquals('4242', $domainEntity->last4);
        $this->assertEquals(12, $domainEntity->exp_month);
        $this->assertEquals(2025, $domainEntity->exp_year);
        $this->assertEquals(1, $domainEntity->id);
    }

    #[Test]
    public function it_handles_null_values_for_optional_fields_in_to_domain(): void
    {
        // Arrange
        $eloquentModel = new PaymentMethod();
        $eloquentModel->user_id = 100;
        $eloquentModel->stripe_payment_method_id = 'pm_987654321';
        $eloquentModel->brand = null;
        $eloquentModel->last4 = null;
        $eloquentModel->exp_month = null;
        $eloquentModel->exp_year = null;
        $eloquentModel->id = null;

        // Act
        $domainEntity = PaymentMethodMapper::toDomain($eloquentModel);

        // Assert
        $this->assertEquals(100, $domainEntity->user_id);
        $this->assertEquals('pm_987654321', $domainEntity->stripe_payment_method_id);

        // Campos opcionales nulos
        $this->assertNull($domainEntity->brand);
        $this->assertNull($domainEntity->last4);
        $this->assertNull($domainEntity->exp_month);
        $this->assertNull($domainEntity->exp_year);
        $this->assertNull($domainEntity->id);
    }

    #[Test]
    public function it_handles_empty_strings_for_optional_fields_in_to_domain(): void
    {
        // Arrange
        $eloquentModel = new PaymentMethod();
        $eloquentModel->user_id = 100;
        $eloquentModel->stripe_payment_method_id = 'pm_111222333';
        $eloquentModel->brand = '';
        $eloquentModel->last4 = '';
        $eloquentModel->exp_month = 0;
        $eloquentModel->exp_year = 0;
        $eloquentModel->id = null;

        // Act
        $domainEntity = PaymentMethodMapper::toDomain($eloquentModel);

        // Assert
        $this->assertEquals(100, $domainEntity->user_id);
        $this->assertEquals('pm_111222333', $domainEntity->stripe_payment_method_id);

        // Campos opcionales con strings vacíos
        $this->assertEquals('', $domainEntity->brand);
        $this->assertEquals('', $domainEntity->last4);
        $this->assertEquals(0, $domainEntity->exp_month);
        $this->assertEquals(0, $domainEntity->exp_year);
        $this->assertNull($domainEntity->id);
    }

    #[Test]
    public function it_maps_from_domain_to_persistence_array_correctly(): void
    {
        // Arrange
        $domainEntity = new DomainPaymentMethod(
            user_id: 200,
            stripe_payment_method_id: 'pm_abcdef123',
            brand: 'mastercard',
            last4: '8888',
            exp_month: 06,
            exp_year: 2026,
            id: 5
        );

        // Act
        $persistenceArray = PaymentMethodMapper::toPersistence($domainEntity);

        // Assert
        $this->assertIsArray($persistenceArray);

        // Campos obligatorios
        $this->assertEquals(200, $persistenceArray['user_id']);
        $this->assertEquals('pm_abcdef123', $persistenceArray['stripe_payment_method_id']);

        // Campos opcionales
        $this->assertEquals('mastercard', $persistenceArray['brand']);
        $this->assertEquals('8888', $persistenceArray['last4']);
        $this->assertEquals(06, $persistenceArray['exp_month']);
        $this->assertEquals(2026, $persistenceArray['exp_year']);

        // Campos que NO deben estar en persistencia
        $this->assertArrayNotHasKey('id', $persistenceArray);
    }

    #[Test]
    public function it_handles_null_values_in_domain_to_persistence(): void
    {
        // Arrange
        $domainEntity = new DomainPaymentMethod(
            user_id: 300,
            stripe_payment_method_id: 'pm_xyz789',
            brand: null,
            last4: null,
            exp_month: null,
            exp_year: null,
            id: null
        );

        // Act
        $persistenceArray = PaymentMethodMapper::toPersistence($domainEntity);

        // Assert
        $this->assertEquals(300, $persistenceArray['user_id']);
        $this->assertEquals('pm_xyz789', $persistenceArray['stripe_payment_method_id']);

        // Campos opcionales nulos
        $this->assertNull($persistenceArray['brand']);
        $this->assertNull($persistenceArray['last4']);
        $this->assertNull($persistenceArray['exp_month']);
        $this->assertNull($persistenceArray['exp_year']);

        // id no debe estar
        $this->assertArrayNotHasKey('id', $persistenceArray);
    }

    #[Test]
    public function it_handles_empty_strings_in_domain_to_persistence(): void
    {
        // Arrange
        $domainEntity = new DomainPaymentMethod(
            user_id: 400,
            stripe_payment_method_id: 'pm_empty123',
            brand: '',
            last4: '',
            exp_month: 0,
            exp_year: 0,
            id: null
        );

        // Act
        $persistenceArray = PaymentMethodMapper::toPersistence($domainEntity);

        // Assert
        $this->assertEquals(400, $persistenceArray['user_id']);
        $this->assertEquals('pm_empty123', $persistenceArray['stripe_payment_method_id']);

        // Campos opcionales con strings vacíos
        $this->assertEquals('', $persistenceArray['brand']);
        $this->assertEquals('', $persistenceArray['last4']);
        $this->assertEquals(0, $persistenceArray['exp_month']);
        $this->assertEquals(0, $persistenceArray['exp_year']);
    }

    #[Test]
    public function it_preserves_data_in_bidirectional_mapping(): void
    {
        // Arrange
        $originalData = [
            'user_id' => 150,
            'stripe_payment_method_id' => 'pm_bidirectional_123',
            'brand' => 'amex',
            'last4' => '1234',
            'exp_month' => 03,
            'exp_year' => 2027,
        ];

        // Crear modelo Eloquent
        $eloquentModel = new PaymentMethod();
        foreach ($originalData as $key => $value) {
            $eloquentModel->$key = $value;
        }
        $eloquentModel->id = 10;

        // Eloquent -> Domain
        $domainEntity = PaymentMethodMapper::toDomain($eloquentModel);

        // Verificar mapeo a dominio
        $this->assertEquals(10, $domainEntity->id);
        $this->assertEquals(150, $domainEntity->user_id);
        $this->assertEquals('pm_bidirectional_123', $domainEntity->stripe_payment_method_id);
        $this->assertEquals('amex', $domainEntity->brand);
        $this->assertEquals('1234', $domainEntity->last4);
        $this->assertEquals(03, $domainEntity->exp_month);
        $this->assertEquals(2027, $domainEntity->exp_year);

        // Domain -> Persistence
        $persistenceArray = PaymentMethodMapper::toPersistence($domainEntity);

        // Comparar con datos originales (excluyendo id)
        $this->assertEquals($originalData, $persistenceArray);
    }

    #[Test]
    public function it_maps_different_credit_card_brands(): void
    {
        $brandTestCases = [
            'visa',
            'mastercard',
            'amex',
            'discover',
            'jcb',
            'diners',
            'unionpay',
            null, // También prueba null
            '',   // Y string vacío
        ];

        foreach ($brandTestCases as $brand) {
            // To Domain
            $eloquentModel = new PaymentMethod();
            $eloquentModel->user_id = 100;
            $eloquentModel->stripe_payment_method_id = 'pm_test_' . ($brand ?? 'null');
            $eloquentModel->brand = $brand;

            $domainEntity = PaymentMethodMapper::toDomain($eloquentModel);

            $this->assertEquals($brand, $domainEntity->brand);

            // To Persistence
            $persistenceArray = PaymentMethodMapper::toPersistence($domainEntity);

            $this->assertEquals($brand, $persistenceArray['brand']);
        }
    }

    #[Test]
    public function it_maps_different_last4_formats(): void
    {
        $last4TestCases = [
            '4242',
            '1234',
            '0000',
            '9999',
            null,
            '',
        ];

        foreach ($last4TestCases as $last4) {
            // To Domain
            $eloquentModel = new PaymentMethod();
            $eloquentModel->user_id = 100;
            $eloquentModel->stripe_payment_method_id = 'pm_test_' . ($last4 ?? 'null');
            $eloquentModel->last4 = $last4;

            $domainEntity = PaymentMethodMapper::toDomain($eloquentModel);

            $this->assertEquals($last4, $domainEntity->last4);

            // To Persistence
            $persistenceArray = PaymentMethodMapper::toPersistence($domainEntity);

            $this->assertEquals($last4, $persistenceArray['last4']);
        }
    }

    #[Test]
    public function it_maps_expiration_dates_correctly(): void
    {
        // Test casos límite para fechas de expiración
        $expirationTestCases = [
            ['month' => 01, 'year' => 2024],
            ['month' => 12, 'year' => 2030],
            ['month' => 06, 'year' => 2025],
            ['month' => null, 'year' => 2024],
            ['month' => 12, 'year' => null],
            ['month' => null, 'year' => null],
            ['month' => 0, 'year' => 0],
        ];

        foreach ($expirationTestCases as $testCase) {
            // To Domain
            $eloquentModel = new PaymentMethod();
            $eloquentModel->user_id = 100;
            $eloquentModel->stripe_payment_method_id = 'pm_exp_test';
            $eloquentModel->exp_month = $testCase['month'];
            $eloquentModel->exp_year = $testCase['year'];

            $domainEntity = PaymentMethodMapper::toDomain($eloquentModel);

            $this->assertEquals($testCase['month'], $domainEntity->exp_month);
            $this->assertEquals($testCase['year'], $domainEntity->exp_year);

            // To Persistence
            $persistenceArray = PaymentMethodMapper::toPersistence($domainEntity);

            $this->assertEquals($testCase['month'], $persistenceArray['exp_month']);
            $this->assertEquals($testCase['year'], $persistenceArray['exp_year']);
        }
    }

    #[Test]
    public function it_excludes_id_from_persistence_array(): void
    {
        // Verifica que id nunca esté en el array de persistencia
        $testCases = [
            ['id' => 1],
            ['id' => null],
            ['id' => 999],
        ];

        foreach ($testCases as $testCase) {
            $domainEntity = new DomainPaymentMethod(
                user_id: 100,
                stripe_payment_method_id: 'pm_test',
                brand: 'visa',
                last4: '1234',
                exp_month: 12,
                exp_year: 2025,
                id: $testCase['id']
            );

            $persistenceArray = PaymentMethodMapper::toPersistence($domainEntity);

            $this->assertArrayNotHasKey('id', $persistenceArray);
        }
    }

    #[Test]
    public function it_requires_mandatory_fields(): void
    {
        // Arrange - solo campos obligatorios
        $eloquentModel = new PaymentMethod();
        $eloquentModel->user_id = 500;
        $eloquentModel->stripe_payment_method_id = 'pm_mandatory_only';
        // Campos opcionales no establecidos (serán null por defecto)

        // Act
        $domainEntity = PaymentMethodMapper::toDomain($eloquentModel);

        // Assert
        $this->assertEquals(500, $domainEntity->user_id);
        $this->assertEquals('pm_mandatory_only', $domainEntity->stripe_payment_method_id);

        // Campos opcionales deben ser null
        $this->assertNull($domainEntity->brand);
        $this->assertNull($domainEntity->last4);
        $this->assertNull($domainEntity->exp_month);
        $this->assertNull($domainEntity->exp_year);
        $this->assertNull($domainEntity->id);
    }

    #[Test]
    public function it_handles_long_stripe_payment_method_ids(): void
    {
        $longIds = [
            'pm_1JkLmN2oPqRsTuVwXyZ1234567890abcdef',
            'pm_' . str_repeat('a', 50),
            'src_' . str_repeat('b', 40),
            'card_' . str_repeat('c', 30),
        ];

        foreach ($longIds as $stripeId) {
            $eloquentModel = new PaymentMethod();
            $eloquentModel->user_id = 100;
            $eloquentModel->stripe_payment_method_id = $stripeId;

            $domainEntity = PaymentMethodMapper::toDomain($eloquentModel);

            $this->assertEquals($stripeId, $domainEntity->stripe_payment_method_id);

            $persistenceArray = PaymentMethodMapper::toPersistence($domainEntity);

            $this->assertEquals($stripeId, $persistenceArray['stripe_payment_method_id']);
        }
    }

}
