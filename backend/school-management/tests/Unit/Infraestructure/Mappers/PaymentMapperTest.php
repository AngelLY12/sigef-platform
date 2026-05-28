<?php

namespace Tests\Unit\Infraestructure\Mappers;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use PHPUnit\Framework\Attributes\Test;
use App\Models\Payment;
use App\Core\Domain\Entities\Payment as DomainPayment;
use App\Core\Infraestructure\Mappers\PaymentMapper;
use Carbon\Carbon;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use Tests\TestCase;

class PaymentMapperTest extends TestCase
{
    #[Test]
    public function it_maps_all_fields_from_eloquent_to_domain(): void
    {
        // Arrange
        $eloquentModel = new Payment();

        // Establece propiedades directamente (los enums ya vienen casteados)
        $eloquentModel->id = 1;
        $eloquentModel->user_id = 100;
        $eloquentModel->payment_concept_id = 200;
        $eloquentModel->payment_method_id = 300;
        $eloquentModel->stripe_payment_method_id = 'pm_123456';
        $eloquentModel->concept_name = 'Tuition Fee';
        $eloquentModel->amount = '1000.50';
        $eloquentModel->amount_received = '1000.50';
        $eloquentModel->payment_method_details = ['type' => 'card', 'last4' => '4242'];
        $eloquentModel->status = PaymentStatus::SUCCEEDED; // Ya viene como Enum
        $eloquentModel->payment_intent_id = 'pi_123456';
        $eloquentModel->url = 'https://checkout.stripe.com/test';
        $eloquentModel->stripe_session_id = 'cs_test_123';
        $eloquentModel->created_at = Carbon::parse('2024-01-15 10:30:00');

        // Act
        $domainEntity = PaymentMapper::toDomain($eloquentModel);

        // Assert
        $this->assertInstanceOf(DomainPayment::class, $domainEntity);

        // Campos básicos
        $this->assertEquals(1, $domainEntity->id);
        $this->assertEquals(100, $domainEntity->user_id);
        $this->assertEquals(200, $domainEntity->payment_concept_id);
        $this->assertEquals(300, $domainEntity->payment_method_id);
        $this->assertEquals('pm_123456', $domainEntity->stripe_payment_method_id);

        // Campos de concepto y monto (strings)
        $this->assertEquals('Tuition Fee', $domainEntity->concept_name);
        $this->assertEquals('1000.50', $domainEntity->amount);
        $this->assertEquals('1000.50', $domainEntity->amount_received);

        // Detalles del método de pago
        $this->assertEquals(['type' => 'card', 'last4' => '4242'], $domainEntity->payment_method_details);

        // Estado como Enum (ya viene casteado)
        $this->assertInstanceOf(PaymentStatus::class, $domainEntity->status);
        $this->assertSame(PaymentStatus::SUCCEEDED, $domainEntity->status);

        // Campos de Stripe
        $this->assertEquals('pi_123456', $domainEntity->payment_intent_id);
        $this->assertEquals('https://checkout.stripe.com/test', $domainEntity->url);
        $this->assertEquals('cs_test_123', $domainEntity->stripe_session_id);

        // Fecha como Carbon
        $this->assertInstanceOf(Carbon::class, $domainEntity->created_at);
        $this->assertEquals('2024-01-15 10:30:00', $domainEntity->created_at->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function it_handles_null_values_from_eloquent_to_domain(): void
    {
        // Arrange
        $eloquentModel = new Payment();
        $eloquentModel->user_id = 100;
        $eloquentModel->status = PaymentStatus::DEFAULT; // Enum ya casteado
        $eloquentModel->payment_concept_id = null;
        $eloquentModel->payment_method_id = null;
        $eloquentModel->stripe_payment_method_id = null;
        $eloquentModel->concept_name = 'test concept';
        $eloquentModel->amount = '1000.50';
        $eloquentModel->amount_received = null;
        $eloquentModel->payment_method_details = [];
        $eloquentModel->payment_intent_id = null;
        $eloquentModel->url = null;
        $eloquentModel->stripe_session_id = null;
        $eloquentModel->id = null;

        // Act
        $domainEntity = PaymentMapper::toDomain($eloquentModel);

        // Assert
        $this->assertNull($domainEntity->id);
        $this->assertEquals(100, $domainEntity->user_id);

        // Campos nulos
        $this->assertNull($domainEntity->payment_concept_id);
        $this->assertNull($domainEntity->payment_method_id);
        $this->assertNull($domainEntity->stripe_payment_method_id);
        $this->assertNull($domainEntity->amount_received);
        $this->assertEquals([], $domainEntity->payment_method_details); // Array vacío para null
        $this->assertNull($domainEntity->payment_intent_id);
        $this->assertNull($domainEntity->url);
        $this->assertNull($domainEntity->stripe_session_id);

        // Estado como Enum
        $this->assertInstanceOf(PaymentStatus::class, $domainEntity->status);
        $this->assertSame(PaymentStatus::DEFAULT, $domainEntity->status);
    }

    #[Test]
    public function it_handles_empty_array_for_null_payment_method_details(): void
    {
        // Arrange
        $eloquentModel = new Payment();
        $eloquentModel->user_id = 100;
        $eloquentModel->concept_name = 'test concept';
        $eloquentModel->amount = '1000.50';
        $eloquentModel->status = PaymentStatus::DEFAULT;
        $eloquentModel->payment_method_details = [];

        // Act
        $domainEntity = PaymentMapper::toDomain($eloquentModel);

        // Assert
        $this->assertEquals([], $domainEntity->payment_method_details);
    }

    #[Test]
    public function it_handles_array_for_payment_method_details(): void
    {
        // Arrange
        $testCases = [
            ['type' => 'card', 'last4' => '4242'],
            ['type' => 'bank_transfer', 'bank' => 'STP'],
            [], // array vacío
            ['custom_field' => 'custom_value'],
        ];

        foreach ($testCases as $testCase) {
            $eloquentModel = new Payment();
            $eloquentModel->user_id = 100;
            $eloquentModel->concept_name = 'test concept';
            $eloquentModel->amount = '1000.50';
            $eloquentModel->status = PaymentStatus::DEFAULT;
            $eloquentModel->payment_method_details = $testCase;

            $domainEntity = PaymentMapper::toDomain($eloquentModel);

            $this->assertEquals($testCase, $domainEntity->payment_method_details);
        }
    }

    #[Test]
    public function it_maps_from_domain_to_persistence_array_correctly(): void
    {
        // Arrange
        $domainEntity = new DomainPayment(
            concept_name: 'Tuition Fee',
            amount: '1000.50',
            status: PaymentStatus::SUCCEEDED,
            payment_method_details: ['type' => 'card', 'last4' => '4242'],
            id: 1,
            user_id: 100,
            payment_concept_id: 200,
            payment_method_id: 300,
            stripe_payment_method_id: 'pm_123456',
            amount_received: '1000.50',
            payment_intent_id: 'pi_123456',
            url: 'https://checkout.stripe.com/test',
            stripe_session_id: 'cs_test_123',
            created_at: Carbon::parse('2024-01-15 10:30:00')
        );

        // Act
        $persistenceArray = PaymentMapper::toPersistence($domainEntity);

        // Assert
        $this->assertIsArray($persistenceArray);

        // Campos obligatorios
        $this->assertEquals(100, $persistenceArray['user_id']);
        $this->assertEquals(200, $persistenceArray['payment_concept_id']);
        $this->assertEquals(300, $persistenceArray['payment_method_id']);

        // Campos de Stripe
        $this->assertEquals('pm_123456', $persistenceArray['stripe_payment_method_id']);
        $this->assertEquals('pi_123456', $persistenceArray['payment_intent_id']);
        $this->assertEquals('cs_test_123', $persistenceArray['stripe_session_id']);

        // Campos de concepto y monto
        $this->assertEquals('Tuition Fee', $persistenceArray['concept_name']);
        $this->assertEquals('1000.50', $persistenceArray['amount']);
        $this->assertEquals('1000.50', $persistenceArray['amount_received']);

        // Detalles del método de pago
        $this->assertEquals(['type' => 'card', 'last4' => '4242'], $persistenceArray['payment_method_details']);

        // Estado como string del Enum (se convierte automáticamente)
        $this->assertEquals(PaymentStatus::SUCCEEDED, $persistenceArray['status']);

        // URL
        $this->assertEquals('https://checkout.stripe.com/test', $persistenceArray['url']);

        // Campos que NO deben estar
        $this->assertArrayNotHasKey('id', $persistenceArray);
        $this->assertArrayNotHasKey('created_at', $persistenceArray);
    }

    #[Test]
    public function it_handles_null_values_in_domain_to_persistence(): void
    {
        // Arrange
        $domainEntity = new DomainPayment(
            concept_name: 'Test concept',
            amount: '1000.50',
            status: PaymentStatus::DEFAULT,
            payment_method_details: [],
            id: null,
            user_id: 100,
            payment_concept_id: null,
            payment_method_id: null,
            stripe_payment_method_id: null,
            amount_received: null,
            payment_intent_id: null,
            url: null,
            stripe_session_id: null,
            created_at: null
        );

        // Act
        $persistenceArray = PaymentMapper::toPersistence($domainEntity);

        // Assert
        $this->assertEquals(100, $persistenceArray['user_id']);

        // Campos nulos
        $this->assertNull($persistenceArray['payment_concept_id']);
        $this->assertNull($persistenceArray['payment_method_id']);
        $this->assertNull($persistenceArray['stripe_payment_method_id']);
        $this->assertNull($persistenceArray['amount_received']);
        $this->assertEquals([],$persistenceArray['payment_method_details']);
        $this->assertNull($persistenceArray['payment_intent_id']);
        $this->assertNull($persistenceArray['url']);
        $this->assertNull($persistenceArray['stripe_session_id']);

        // Estado como string
        $this->assertEquals(PaymentStatus::DEFAULT, $persistenceArray['status']);
    }

    #[Test]
    public function it_handles_all_payment_status_enum_values(): void
    {
        $statusTestCases = [
            PaymentStatus::SUCCEEDED,
            PaymentStatus::REQUIRES_ACTION,
            PaymentStatus::PAID,
            PaymentStatus::UNPAID,
            PaymentStatus::DEFAULT,
            PaymentStatus::OVERPAID,
            PaymentStatus::UNDERPAID,
        ];

        foreach ($statusTestCases as $status) {
            // To Domain - el modelo ya tiene el Enum
            $eloquentModel = new Payment();
            $eloquentModel->user_id = 100;
            $eloquentModel->concept_name = 'test concept';
            $eloquentModel->amount = '1000.50';
            $eloquentModel->status = $status;

            $domainEntity = PaymentMapper::toDomain($eloquentModel);

            $this->assertInstanceOf(PaymentStatus::class, $domainEntity->status);
            $this->assertSame($status, $domainEntity->status);

            // To Persistence - el Enum se convierte a string
            $persistenceArray = PaymentMapper::toPersistence($domainEntity);

            $this->assertEquals($status, $persistenceArray['status']);
        }
    }

    #[Test]
    public function it_preserves_data_in_bidirectional_mapping(): void
    {
        // Test completo de ida y vuelta
        $originalData = [
            'user_id' => 150,
            'payment_concept_id' => 250,
            'payment_method_id' => 350,
            'stripe_payment_method_id' => 'pm_test_789',
            'concept_name' => 'Lab Fee',
            'amount' => '500.75',
            'amount_received' => '500.75',
            'payment_method_details' => ['type' => 'oxxo'],
            'status' => PaymentStatus::PAID, // Enum
            'payment_intent_id' => 'pi_test_789',
            'url' => 'https://example.com/payment',
            'stripe_session_id' => 'cs_test_789',
            'created_at' => Carbon::parse('2024-02-20 14:45:00'),
        ];

        // Crear modelo Eloquent
        $eloquentModel = new Payment();
        foreach ($originalData as $key => $value) {
            $eloquentModel->$key = $value;
        }
        $eloquentModel->id = 2;

        // Eloquent -> Domain
        $domainEntity = PaymentMapper::toDomain($eloquentModel);

        // Verificar mapeo a dominio
        $this->assertEquals(2, $domainEntity->id);
        $this->assertEquals(150, $domainEntity->user_id);
        $this->assertEquals(250, $domainEntity->payment_concept_id);
        $this->assertEquals(350, $domainEntity->payment_method_id);
        $this->assertEquals('pm_test_789', $domainEntity->stripe_payment_method_id);
        $this->assertEquals('Lab Fee', $domainEntity->concept_name);
        $this->assertEquals('500.75', $domainEntity->amount);
        $this->assertEquals('500.75', $domainEntity->amount_received);
        $this->assertEquals(['type' => 'oxxo'], $domainEntity->payment_method_details);
        $this->assertSame(PaymentStatus::PAID, $domainEntity->status);
        $this->assertEquals('pi_test_789', $domainEntity->payment_intent_id);
        $this->assertEquals('https://example.com/payment', $domainEntity->url);
        $this->assertEquals('cs_test_789', $domainEntity->stripe_session_id);
        $this->assertEquals('2024-02-20 14:45:00', $domainEntity->created_at->format('Y-m-d H:i:s'));

        // Domain -> Persistence
        $persistenceArray = PaymentMapper::toPersistence($domainEntity);

        // Comparar con datos originales (excluyendo id y created_at, convirtiendo Enum a string)
        $expectedPersistence = [
            'user_id' => 150,
            'payment_concept_id' => 250,
            'payment_method_id' => 350,
            'stripe_payment_method_id' => 'pm_test_789',
            'concept_name' => 'Lab Fee',
            'amount' => '500.75',
            'amount_received' => '500.75',
            'payment_method_details' => ['type' => 'oxxo'],
            'status' => PaymentStatus::PAID,
            'payment_intent_id' => 'pi_test_789',
            'url' => 'https://example.com/payment',
            'stripe_session_id' => 'cs_test_789',
        ];

        $this->assertEquals($expectedPersistence, $persistenceArray);
    }

    #[Test]
    public function it_handles_different_amount_formats(): void
    {
        $amountTestCases = [
            '1000.50',
            '0.00',
            '1234.56',
            '999999.99',
        ];

        foreach ($amountTestCases as $amount) {
            // To Domain
            $eloquentModel = new Payment();
            $eloquentModel->user_id = 100;
            $eloquentModel->status = PaymentStatus::DEFAULT;
            $eloquentModel->concept_name = 'test concept';
            $eloquentModel->amount = $amount;
            $eloquentModel->amount_received = $amount;

            $domainEntity = PaymentMapper::toDomain($eloquentModel);

            $this->assertEquals($amount, $domainEntity->amount);
            $this->assertEquals($amount, $domainEntity->amount_received);

            // To Persistence
            $persistenceArray = PaymentMapper::toPersistence($domainEntity);

            $this->assertEquals($amount, $persistenceArray['amount']);
            $this->assertEquals($amount, $persistenceArray['amount_received']);
        }
    }

    #[Test]
    public function it_handles_empty_strings_for_optional_fields(): void
    {
        // Arrange
        $eloquentModel = new Payment();
        $eloquentModel->user_id = 100;
        $eloquentModel->status = PaymentStatus::DEFAULT;
        $eloquentModel->concept_name = '';
        $eloquentModel->amount = '100.00';
        $eloquentModel->amount_received = '0.00';
        $eloquentModel->payment_intent_id = '';
        $eloquentModel->url = '';
        $eloquentModel->stripe_session_id = '';

        // Act
        $domainEntity = PaymentMapper::toDomain($eloquentModel);

        // Assert
        $this->assertEquals('', $domainEntity->concept_name);
        $this->assertEquals('', $domainEntity->payment_intent_id);
        $this->assertEquals('', $domainEntity->url);
        $this->assertEquals('', $domainEntity->stripe_session_id);
    }

    #[Test]
    public function it_parses_created_at_from_string_to_carbon(): void
    {
        $dateTestCases = [
            '2024-01-15 10:30:00',
            '2024-12-31 23:59:59',
            '2023-06-01 00:00:00',
            null, // también prueba null
        ];

        foreach ($dateTestCases as $dateString) {
            $eloquentModel = new Payment();
            $eloquentModel->user_id = 100;
            $eloquentModel->concept_name = 'test concept';
            $eloquentModel->amount = '1000.50';
            $eloquentModel->status = PaymentStatus::DEFAULT;
            $eloquentModel->created_at = $dateString ? Carbon::parse($dateString) : null;

            $domainEntity = PaymentMapper::toDomain($eloquentModel);

            if ($dateString) {
                $this->assertInstanceOf(Carbon::class, $domainEntity->created_at);
                $this->assertEquals($dateString, $domainEntity->created_at->format('Y-m-d H:i:s'));
            } else {
                $this->assertNull($domainEntity->created_at);
            }
        }
    }

    #[Test]
    public function it_converts_enum_to_string_in_persistence(): void
    {
        // Verifica específicamente la conversión de Enum a string
        $domainEntity = new DomainPayment(
            concept_name: 'Test',
            amount: '100',
            status: PaymentStatus::REQUIRES_ACTION,
            payment_method_details: [],
            id: 1,
            user_id: 100,
            payment_concept_id: 1,
            payment_method_id: 1,
            stripe_payment_method_id: 'pm_test',
            amount_received: '100',
            payment_intent_id: 'pi_test',
            url: 'https://test.com',
            stripe_session_id: 'cs_test',
            created_at: Carbon::now()
        );

        $persistenceArray = PaymentMapper::toPersistence($domainEntity);

        $this->assertIsObject($persistenceArray['status']);
        $this->assertEquals(PaymentStatus::REQUIRES_ACTION, $persistenceArray['status']);
    }

}
