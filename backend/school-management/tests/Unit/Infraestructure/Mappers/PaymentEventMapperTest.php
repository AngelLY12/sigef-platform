<?php

namespace Tests\Unit\Infraestructure\Mappers;

use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Infraestructure\Mappers\PaymentEventMapper;
use App\Models\Payment;
use App\Models\PaymentEvent as EloquentPaymentEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentEventMapperTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_maps_eloquent_model_to_domain_entity()
    {
        // Arrange
        $eloquentModel = EloquentPaymentEvent::factory()->create();

        // Act
        $domainEntity = PaymentEventMapper::toDomain($eloquentModel);

        // Assert
        $this->assertInstanceOf(PaymentEvent::class, $domainEntity);
        $this->assertEquals($eloquentModel->id, $domainEntity->id);
        $this->assertEquals($eloquentModel->payment_id, $domainEntity->paymentId);
        $this->assertEquals($eloquentModel->stripe_event_id, $domainEntity->stripeEventId);
        $this->assertEquals($eloquentModel->stripe_payment_intent_id, $domainEntity->stripePaymentIntentId);
        $this->assertEquals($eloquentModel->stripe_session_id, $domainEntity->stripeSessionId);
        $this->assertEquals($eloquentModel->event_type, $domainEntity->eventType);
        $this->assertEquals($eloquentModel->metadata, $domainEntity->metadata);
        $this->assertEquals($eloquentModel->amount_received, $domainEntity->amountReceived);
        $this->assertEquals($eloquentModel->status, $domainEntity->status);
        $this->assertEquals($eloquentModel->processed, $domainEntity->processed);
        $this->assertEquals($eloquentModel->error_message, $domainEntity->errorMessage);
        $this->assertEquals($eloquentModel->retry_count, $domainEntity->retryCount);
        $this->assertEquals($eloquentModel->processed_at, $domainEntity->processedAt);
        $this->assertEquals($eloquentModel->created_at, $domainEntity->createdAt);
        $this->assertEquals($eloquentModel->updated_at, $domainEntity->updatedAt);
    }

    #[Test]
    public function it_maps_eloquent_model_with_null_values_to_domain_entity()
    {
        // Arrange
        $eloquentModel = EloquentPaymentEvent::factory()->create([
            'payment_id' => null,
            'stripe_event_id' => null,
            'stripe_payment_intent_id' => null,
            'stripe_session_id' => null,
            'metadata' => null,
            'amount_received' => null,
            'status' => null,
            'error_message' => null,
            'processed_at' => null,
        ]);

        // Act
        $domainEntity = PaymentEventMapper::toDomain($eloquentModel);

        // Assert
        $this->assertNull($domainEntity->paymentId);
        $this->assertNull($domainEntity->stripeEventId);
        $this->assertNull($domainEntity->stripePaymentIntentId);
        $this->assertNull($domainEntity->stripeSessionId);
        $this->assertNull($domainEntity->metadata);
        $this->assertNull($domainEntity->amountReceived);
        $this->assertNull($domainEntity->status);
        $this->assertNull($domainEntity->errorMessage);
        $this->assertNull($domainEntity->processedAt);
    }

    #[Test]
    public function it_maps_domain_entity_to_eloquent_array()
    {
        $paymentId = Payment::factory()->create()->id;
        // Arrange
        $domainEntity = new PaymentEvent(
            id: 999, // ID no debe incluirse en el array de Eloquent
            paymentId: $paymentId,
            stripeEventId: 'evt_test123',
            stripePaymentIntentId: 'pi_test123',
            stripeSessionId: 'cs_test123',
            eventType: PaymentEventType::WEBHOOK_SESSION_COMPLETED,
            metadata: ['custom_field' => 'value'],
            amountReceived: '99.99',
            status: PaymentStatus::DEFAULT,
            processed: false,
            errorMessage: 'Initial processing',
            retryCount: 1,
            processedAt: Carbon::parse('2024-01-16 09:00:00'),
            createdAt: Carbon::parse('2024-01-16 08:55:00'),
            updatedAt: Carbon::parse('2024-01-16 08:56:00'),
        );

        // Act
        $eloquentArray = PaymentEventMapper::toEloquent($domainEntity);

        // Assert
        $this->assertIsArray($eloquentArray);

        // Verificar que contiene todos los campos correctos
        $this->assertEquals($domainEntity->paymentId, $eloquentArray['payment_id']);
        $this->assertEquals($domainEntity->stripeEventId, $eloquentArray['stripe_event_id']);
        $this->assertEquals($domainEntity->stripePaymentIntentId, $eloquentArray['stripe_payment_intent_id']);
        $this->assertEquals($domainEntity->stripeSessionId, $eloquentArray['stripe_session_id']);
        $this->assertEquals($domainEntity->eventType, $eloquentArray['event_type']);
        $this->assertEquals($domainEntity->metadata, $eloquentArray['metadata']);
        $this->assertEquals($domainEntity->amountReceived, $eloquentArray['amount_received']);
        $this->assertEquals($domainEntity->status, $eloquentArray['status']);
        $this->assertEquals($domainEntity->processed, $eloquentArray['processed']);
        $this->assertEquals($domainEntity->errorMessage, $eloquentArray['error_message']);
        $this->assertEquals($domainEntity->retryCount, $eloquentArray['retry_count']);
        $this->assertEquals($domainEntity->processedAt, $eloquentArray['processed_at']);

        // Verificar que NO incluye campos que no deberían estar
        $this->assertArrayNotHasKey('id', $eloquentArray);
        $this->assertArrayNotHasKey('created_at', $eloquentArray);
        $this->assertArrayNotHasKey('updated_at', $eloquentArray);
    }

    #[Test]
    public function it_maps_domain_entity_with_null_values_to_eloquent_array()
    {
        // Arrange
        $domainEntity = new PaymentEvent(
            id: null,
            paymentId: null,
            stripeEventId: null,
            stripePaymentIntentId: null,
            stripeSessionId: null,
            eventType: PaymentEventType::WEBHOOK_PAYMENT_REQUIRES_ACTION,
            metadata: null,
            amountReceived: null,
            status: null,
            processed: false,
            errorMessage: null,
            retryCount: 0,
            processedAt: null,
            createdAt: null,
            updatedAt: null,
        );

        // Act
        $eloquentArray = PaymentEventMapper::toEloquent($domainEntity);

        // Assert
        $this->assertIsArray($eloquentArray);
        $this->assertNull($eloquentArray['payment_id']);
        $this->assertNull($eloquentArray['stripe_event_id']);
        $this->assertNull($eloquentArray['stripe_payment_intent_id']);
        $this->assertNull($eloquentArray['stripe_session_id']);
        $this->assertNull($eloquentArray['metadata']);
        $this->assertNull($eloquentArray['amount_received']);
        $this->assertNull($eloquentArray['status']);
        $this->assertNull($eloquentArray['error_message']);
        $this->assertNull($eloquentArray['processed_at']);
        $this->assertEquals(PaymentEventType::WEBHOOK_PAYMENT_REQUIRES_ACTION, $eloquentArray['event_type']);
        $this->assertFalse($eloquentArray['processed']);
        $this->assertEquals(0, $eloquentArray['retry_count']);
    }

    #[Test]
    public function it_correctly_maps_enum_values()
    {
        // Arrange - Crear con diferentes tipos de enum
        $eventTypes = [
            PaymentEventType::WEBHOOK_SESSION_COMPLETED,
            PaymentEventType::WEBHOOK_PAYMENT_SUCCEEDED,
            PaymentEventType::WEBHOOK_PAYMENT_FAILED,
            PaymentEventType::WEBHOOK_SESSION_ASYNC_COMPLETED,
            PaymentEventType::RECONCILIATION_STARTED,
            PaymentEventType::RECONCILIATION_COMPLETED,
            PaymentEventType::RECONCILIATION_FAILED,
            PaymentEventType::EMAIL_PAYMENT_CREATED,
        ];

        $statuses = [
            PaymentStatus::DEFAULT,
            PaymentStatus::SUCCEEDED,
            PaymentStatus::FAILED,
            PaymentStatus::PAID,
            PaymentStatus::OVERPAID,
        ];

        foreach ($eventTypes as $eventType) {
            foreach ($statuses as $status) {
                $eloquentModel = EloquentPaymentEvent::factory()->create([
                    'event_type' => $eventType,
                    'status' => $status,
                ]);

                // Act
                $domainEntity = PaymentEventMapper::toDomain($eloquentModel);

                // Assert
                $this->assertEquals($eventType, $domainEntity->eventType);
                $this->assertEquals($status, $domainEntity->status);

                // Test round-trip
                $eloquentArray = PaymentEventMapper::toEloquent($domainEntity);
                $this->assertEquals($eventType, $eloquentArray['event_type']);
                $this->assertEquals($status, $eloquentArray['status']);
            }
        }
    }

    #[Test]
    public function it_correctly_maps_metadata_array()
    {
        // Arrange
        $metadataTestCases = [
            null,
            [],
            ['simple' => 'value'],
            ['nested' => ['level1' => ['level2' => 'deep']]],
            ['multiple' => 'values', 'numbers' => 123, 'booleans' => true, 'nulls' => null],
            ['array' => [1, 2, 3]],
        ];

        foreach ($metadataTestCases as $metadata) {
            $eloquentModel = EloquentPaymentEvent::factory()->create([
                'metadata' => $metadata,
            ]);

            // Act
            $domainEntity = PaymentEventMapper::toDomain($eloquentModel);

            // Assert
            $this->assertEquals($metadata, $domainEntity->metadata);

            $paymentId = Payment::factory()->create()->id;
            // Test round-trip
            $newDomainEntity = new PaymentEvent(
                id: null,
                paymentId: $paymentId,
                stripeEventId: 'test',
                stripePaymentIntentId: 'test',
                stripeSessionId: 'test',
                eventType: PaymentEventType::WEBHOOK_SESSION_ASYNC_COMPLETED,
                metadata: $metadata,
                amountReceived: '10.00',
                status: PaymentStatus::DEFAULT,
                processed: false,
            );

            $eloquentArray = PaymentEventMapper::toEloquent($newDomainEntity);
            $this->assertEquals($metadata, $eloquentArray['metadata']);
        }
    }

    #[Test]
    public function it_correctly_maps_decimal_amounts()
    {
        // Arrange
        $amountTestCases = [
            null,
            '0.00',
            '10.50',
            '100.00',
            '999.99',
            '1000.00',
            '123456.78',
        ];

        foreach ($amountTestCases as $amount) {
            $eloquentModel = EloquentPaymentEvent::factory()->create([
                'amount_received' => $amount,
            ]);

            // Act
            $domainEntity = PaymentEventMapper::toDomain($eloquentModel);

            // Assert
            $this->assertEquals($amount, $domainEntity->amountReceived);

            $paymentId = Payment::factory()->create()->id;
            // Test round-trip
            $newDomainEntity = new PaymentEvent(
                id: null,
                paymentId: $paymentId,
                stripeEventId: 'test',
                stripePaymentIntentId: 'test',
                stripeSessionId: 'test',
                eventType: PaymentEventType::WEBHOOK_SESSION_ASYNC_COMPLETED,
                metadata: [],
                amountReceived: $amount,
                status: PaymentStatus::DEFAULT,
                processed: false,
            );

            $eloquentArray = PaymentEventMapper::toEloquent($newDomainEntity);
            $this->assertEquals($amount, $eloquentArray['amount_received']);
        }
    }

    #[Test]
    public function it_correctly_maps_boolean_processed_field()
    {
        // Arrange
        $processedTestCases = [true, false];

        foreach ($processedTestCases as $processed) {
            $eloquentModel = EloquentPaymentEvent::factory()->create([
                'processed' => $processed,
            ]);

            // Act
            $domainEntity = PaymentEventMapper::toDomain($eloquentModel);

            // Assert
            $this->assertEquals($processed, $domainEntity->processed);

            $paymentId = Payment::factory()->create()->id;
            // Test round-trip
            $newDomainEntity = new PaymentEvent(
                id: null,
                paymentId: $paymentId,
                stripeEventId: 'test',
                stripePaymentIntentId: 'test',
                stripeSessionId: 'test',
                eventType: PaymentEventType::WEBHOOK_SESSION_COMPLETED,
                metadata: [],
                amountReceived: '10.00',
                status: PaymentStatus::DEFAULT,
                processed: $processed,
            );

            $eloquentArray = PaymentEventMapper::toEloquent($newDomainEntity);
            $this->assertEquals($processed, $eloquentArray['processed']);
        }
    }

    #[Test]
    public function it_correctly_maps_integer_retry_count()
    {
        // Arrange
        $retryCountTestCases = [0, 1, 2, 3, 5, 10, 100];

        foreach ($retryCountTestCases as $retryCount) {
            $eloquentModel = EloquentPaymentEvent::factory()->create([
                'retry_count' => $retryCount,
            ]);

            // Act
            $domainEntity = PaymentEventMapper::toDomain($eloquentModel);

            // Assert
            $this->assertEquals($retryCount, $domainEntity->retryCount);

            $paymentId = Payment::factory()->create()->id;
            // Test round-trip
            $newDomainEntity = new PaymentEvent(
                id: null,
                paymentId: $paymentId,
                stripeEventId: 'test',
                stripePaymentIntentId: 'test',
                stripeSessionId: 'test',
                eventType: PaymentEventType::WEBHOOK_SESSION_COMPLETED,
                metadata: [],
                amountReceived: '10.00',
                status: PaymentStatus::SUCCEEDED,
                processed: false,
                errorMessage: null,
                retryCount: $retryCount,
            );

            $eloquentArray = PaymentEventMapper::toEloquent($newDomainEntity);
            $this->assertEquals($retryCount, $eloquentArray['retry_count']);
        }
    }

    #[Test]
    public function it_correctly_maps_datetime_fields()
    {
        // Arrange
        $now = Carbon::now();
        $yesterday = Carbon::yesterday();
        $tomorrow = Carbon::tomorrow();
        $farFuture = Carbon::createFromDate(2030, 1, 1);

        $datetimeTestCases = [
            'processed_at' => [null, $now, $yesterday, $tomorrow, $farFuture],
            'created_at' => [$now, $yesterday],
            'updated_at' => [$now, $tomorrow],
        ];

        foreach ($datetimeTestCases['processed_at'] as $processedAt) {
            $eloquentModel = EloquentPaymentEvent::factory()->create([
                'processed_at' => $processedAt,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Act
            $domainEntity = PaymentEventMapper::toDomain($eloquentModel);

            // Assert
            $this->assertEquals($processedAt ? $processedAt->format('Y-m-d H:i:s'): null, $domainEntity->processedAt?->format('Y-m-d H:i:s'));

            $paymentId = Payment::factory()->create()->id;
            // Test round-trip
            $newDomainEntity = new PaymentEvent(
                id: null,
                paymentId: $paymentId,
                stripeEventId: 'test',
                stripePaymentIntentId: 'test',
                stripeSessionId: 'test',
                eventType: PaymentEventType::WEBHOOK_SESSION_COMPLETED,
                metadata: [],
                amountReceived: '10.00',
                status: PaymentStatus::SUCCEEDED,
                processed: false,
                processedAt: $processedAt,
            );

            $eloquentArray = PaymentEventMapper::toEloquent($newDomainEntity);

            if ($processedAt instanceof Carbon) {
                $this->assertEquals($processedAt->format('Y-m-d H:i:s'), $eloquentArray['processed_at']->format('Y-m-d H:i:s'));
            } else {
                $this->assertEquals($processedAt, $eloquentArray['processed_at']);
            }
        }
    }

    #[Test]
    public function it_creates_complete_round_trip()
    {
        $paymentId = Payment::factory()->create()->id;
        // Arrange
        $originalEloquent = EloquentPaymentEvent::factory()->create([
            'payment_id' => $paymentId,
            'stripe_event_id' => 'evt_roundtrip',
            'stripe_payment_intent_id' => 'pi_roundtrip',
            'stripe_session_id' => 'cs_roundtrip',
            'event_type' => PaymentEventType::RECONCILIATION_COMPLETED,
            'metadata' => ['test' => 'roundtrip', 'number' => 42],
            'amount_received' => '500.25',
            'status' => PaymentStatus::SUCCEEDED,
            'processed' => true,
            'error_message' => 'No errors',
            'retry_count' => 2,
            'processed_at' => Carbon::parse('2024-01-20 15:30:00'),
        ]);

        // Act - Eloquent -> Domain
        $domainEntity = PaymentEventMapper::toDomain($originalEloquent);

        // Domain -> Eloquent array
        $eloquentArray = PaymentEventMapper::toEloquent($domainEntity);

        // Simular creación de nuevo Eloquent model con el array
        $newEloquent = new EloquentPaymentEvent($eloquentArray);
        $newEloquent->id = $originalEloquent->id; // Para comparación
        $newEloquent->created_at = $originalEloquent->created_at;
        $newEloquent->updated_at = $originalEloquent->updated_at;

        // Eloquent -> Domain nuevamente
        $newDomainEntity = PaymentEventMapper::toDomain($newEloquent);

        // Assert - Comparar propiedades
        $this->assertEquals($domainEntity->id, $newDomainEntity->id);
        $this->assertEquals($domainEntity->paymentId, $newDomainEntity->paymentId);
        $this->assertEquals($domainEntity->stripeEventId, $newDomainEntity->stripeEventId);
        $this->assertEquals($domainEntity->stripePaymentIntentId, $newDomainEntity->stripePaymentIntentId);
        $this->assertEquals($domainEntity->stripeSessionId, $newDomainEntity->stripeSessionId);
        $this->assertEquals($domainEntity->eventType, $newDomainEntity->eventType);
        $this->assertEquals($domainEntity->metadata, $newDomainEntity->metadata);
        $this->assertEquals($domainEntity->amountReceived, $newDomainEntity->amountReceived);
        $this->assertEquals($domainEntity->status, $newDomainEntity->status);
        $this->assertEquals($domainEntity->processed, $newDomainEntity->processed);
        $this->assertEquals($domainEntity->errorMessage, $newDomainEntity->errorMessage);
        $this->assertEquals($domainEntity->retryCount, $newDomainEntity->retryCount);

        if ($domainEntity->processedAt && $newDomainEntity->processedAt) {
            $this->assertEquals(
                $domainEntity->processedAt->format('Y-m-d H:i:s'),
                $newDomainEntity->processedAt->format('Y-m-d H:i:s')
            );
        } else {
            $this->assertEquals($domainEntity->processedAt, $newDomainEntity->processedAt);
        }
    }

    #[Test]
    public function it_handles_error_message_correctly()
    {
        // Arrange
        $errorMessageTestCases = [
            null,
            '',
            'Simple error',
            'Error with details: Something went wrong',
            'Long error message with multiple details and stack trace information for debugging purposes',
        ];

        foreach ($errorMessageTestCases as $errorMessage) {
            $eloquentModel = EloquentPaymentEvent::factory()->create([
                'error_message' => $errorMessage,
            ]);

            // Act
            $domainEntity = PaymentEventMapper::toDomain($eloquentModel);

            // Assert
            $this->assertEquals($errorMessage, $domainEntity->errorMessage);

            $paymentId = Payment::factory()->create()->id;
            // Test round-trip
            $newDomainEntity = new PaymentEvent(
                id: null,
                paymentId: $paymentId,
                stripeEventId: 'test',
                stripePaymentIntentId: 'test',
                stripeSessionId: 'test',
                eventType: PaymentEventType::WEBHOOK_SESSION_COMPLETED,
                metadata: [],
                amountReceived: '10.00',
                status: PaymentStatus::DEFAULT,
                processed: false,
                errorMessage: $errorMessage,
            );

            $eloquentArray = PaymentEventMapper::toEloquent($newDomainEntity);
            $this->assertEquals($errorMessage, $eloquentArray['error_message']);
        }
    }

}
