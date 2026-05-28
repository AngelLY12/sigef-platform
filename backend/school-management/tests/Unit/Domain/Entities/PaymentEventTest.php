<?php

namespace Tests\Unit\Domain\Entities;

use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use DateTime;
class PaymentEventTest extends TestCase
{
    #[Test]
    public function test_can_create_webhook_event()
    {
        // Arrange
        $paymentId = 123;
        $stripeEventId = 'evt_1MqLX2LkdIwHu7ixY5p5vXvH';
        $paymentIntentId = 'pi_3MqLX2LkdIwHu7ix1E8WvXvH';
        $sessionId = 'cs_test_a1b2c3d4';
        $amount = '100.00';
        $eventType = PaymentEventType::WEBHOOK_SESSION_COMPLETED;
        $metadata = ['source' => 'stripe_webhook'];

        // Act
        $event = PaymentEvent::createWebhookEvent(
            $paymentId,
            $stripeEventId,
            $paymentIntentId,
            $sessionId,
            $amount,
            $eventType,
            $metadata
        );

        // Assert
        $this->assertNull($event->id);
        $this->assertEquals($paymentId, $event->paymentId);
        $this->assertEquals($stripeEventId, $event->stripeEventId);
        $this->assertEquals($paymentIntentId, $event->stripePaymentIntentId);
        $this->assertEquals($sessionId, $event->stripeSessionId);
        $this->assertEquals($amount, $event->amountReceived);
        $this->assertEquals($eventType, $event->eventType);
        $this->assertEquals($metadata, $event->metadata);
        $this->assertNull($event->status);
        $this->assertFalse($event->processed);
        $this->assertNull($event->errorMessage);
        $this->assertEquals(0, $event->retryCount);
        $this->assertNull($event->processedAt);
    }
    #[Test]
    public function test_can_create_reconciliation_event_with_success_outcome()
    {
        // Arrange
        $paymentId = 456;
        $stripeEvent = 'evt_19y837y7';
        $sessionId = 'cs_test_a1b2c3d4';
        $eventType = PaymentEventType::RECONCILIATION_COMPLETED;
        $piId = 'pi_text_gigiycva72';
        $outcome = 'success';
        $metadata = ['custom' => 'data'];
        $amount = '250.50';
        $status = PaymentStatus::SUCCEEDED;

        // Act
        $event = PaymentEvent::createReconciliationEvent(
            $paymentId,
            $outcome,
            $stripeEvent,
            $sessionId,
            $eventType,
            $piId,
            $metadata,
            $amount,
            null,
            $status
        );

        // Assert
        $this->assertNull($event->id);
        $this->assertEquals($paymentId, $event->paymentId);
        $this->assertEquals($stripeEvent,$event->stripeEventId);
        $this->assertEquals($piId,$event->stripePaymentIntentId);
        $this->assertEquals($sessionId,$event->stripeSessionId);
        $this->assertEquals($amount, $event->amountReceived);
        $this->assertEquals(PaymentEventType::RECONCILIATION_COMPLETED, $event->eventType);
        $this->assertEquals(array_merge($metadata, ['outcome' => $outcome]), $event->metadata);
        $this->assertEquals($status, $event->status);
        $this->assertTrue($event->processed);
        $this->assertNotNull($event->processedAt);
        $this->assertInstanceOf(\DateTime::class, $event->processedAt);
    }

    #[Test]
    public function test_can_create_reconciliation_event_with_failed_outcome()
    {
        // Arrange
        $paymentId = 789;
        $outcome = 'stripe_data_missing';

        // Act
        $event = PaymentEvent::createReconciliationEvent(
            paymentId: $paymentId,
            outcome: $outcome,
            stripeEventId: null,
            stripeSessionId: null,
            eventType: PaymentEventType::RECONCILIATION_FAILED,
            paymentIntentId: null,
        );

        // Assert
        $this->assertEquals(PaymentEventType::RECONCILIATION_FAILED, $event->eventType);
        $this->assertEquals(['outcome' => $outcome], $event->metadata);
        $this->assertTrue($event->processed);
    }

    #[Test]
    public function test_can_create_reconciliation_event_with_started_outcome()
    {
        // Arrange
        $paymentId = 999;
        $outcome = 'some_other_outcome';

        // Act
        $event = PaymentEvent::createReconciliationEvent(
            paymentId: $paymentId,
            outcome: $outcome,
            stripeEventId: null,
            stripeSessionId: null,
            eventType: PaymentEventType::RECONCILIATION_STARTED,
            paymentIntentId: null,
        );

        // Assert
        $this->assertEquals(PaymentEventType::RECONCILIATION_STARTED, $event->eventType);
        $this->assertEquals(['outcome' => $outcome], $event->metadata);
    }
    #[Test]
    public function test_can_create_reconciliation_event_unprocessed()
    {
        // Act

        $event = PaymentEvent::createReconciliationEvent(
            paymentId: 111,
            outcome: 'success',
            stripeEventId: null,
            stripeSessionId: null,
            eventType: PaymentEventType::RECONCILIATION_FAILED,
            paymentIntentId: null,
            processed:false,
        );

        // Assert
        $this->assertFalse($event->processed);
        $this->assertNull($event->processedAt);
    }
    #[Test]
    public function test_can_create_email_event()
    {
        // Arrange
        $paymentId = 222;
        $eventId = "ev_bkbaakdbbj";
        $paymentIntent = "pi_pajppaokdan";
        $sessionId = "cs_aojbdobacbjda";
        $eventType = PaymentEventType::EMAIL_PAYMENT_CREATED;
        $recipientEmail = 'test@example.com';
        $emailData = ['subject' => 'Payment Confirmation'];

        // Act
        $event = PaymentEvent::createEmailEvent(
            $paymentId,
            $eventId,
            $paymentIntent,
            $sessionId,
            $eventType,
            $recipientEmail,
            $emailData
        );

        // Assert
        $this->assertNull($event->id);
        $this->assertEquals($paymentId, $event->paymentId);
        $this->assertEquals($eventId,$event->stripeEventId);
        $this->assertEquals($paymentIntent,$event->stripePaymentIntentId);
        $this->assertEquals($sessionId,$event->stripeSessionId);
        $this->assertEquals($eventType, $event->eventType);
        $this->assertNull($event->amountReceived);
        $this->assertNull($event->status);
        $this->assertFalse($event->processed);
        $this->assertNull($event->processedAt);

        // Verificar metadata específica de email
        $this->assertArrayHasKey('email_status', $event->metadata);
        $this->assertEquals('pending', $event->metadata['email_status']);
        $this->assertEquals($recipientEmail, $event->metadata['recipient_email']);
        $this->assertArrayHasKey('created_at', $event->metadata);
        $this->assertEquals(0, $event->metadata['attempt_count']);
    }
    #[Test]
    public function test_can_create_delivered_email_event()
    {
        // Arrange
        $eventType = PaymentEventType::EMAIL_PAYMENT_FAILED;
        $eventId = "ev_bkbaakdbbj";
        $paymentIntent = "pi_pajppaokdan";
        $sessionId = "cs_aojbdobacbjda";
        // Act
        $event = PaymentEvent::createEmailEvent(
            333,
            $eventId,
            $paymentIntent,
            $sessionId,
            $eventType,
            'user@example.com',
            [],
            'delivered'
        );

        // Assert
        $this->assertTrue($event->processed);
        $this->assertNotNull($event->processedAt);
        $this->assertInstanceOf(DateTime::class, $event->processedAt);
        $this->assertEquals('delivered', $event->metadata['email_status']);
    }
    #[Test]
    public function test_create_email_event_throws_exception_for_non_email_type()
    {
        // Arrange
        $nonEmailType = PaymentEventType::WEBHOOK_PAYMENT_SUCCEEDED;
        $eventId = "ev_bkbaakdbbj";
        $paymentIntent = "pi_pajppaokdan";
        $sessionId = "cs_aojbdobacbjda";
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('El tipo de evento debe ser un email');

        // Act
        PaymentEvent::createEmailEvent(
            444,
            $eventId,
            $paymentIntent,
            $sessionId,
            $nonEmailType,
            'test@example.com'
        );
    }
    #[Test]
    public function test_setters_and_getters_work_correctly()
    {
        // Arrange
        $event = new PaymentEvent(
            id: 1,
            paymentId: 100,
            stripeEventId: 'evt_test',
            stripePaymentIntentId: 'pi_test',
            stripeSessionId: 'cs_test',
            eventType: PaymentEventType::WEBHOOK_PAYMENT_REQUIRES_ACTION,
            metadata: [],
            amountReceived: '50.00',
            status: PaymentStatus::REQUIRES_ACTION,
            processed: false
        );

        // Act - Test setters
        $event->setRetryCount(2);
        $event->setErrorMessage('Processing failed');
        $processedAt = new DateTime('2024-01-15 10:00:00');
        $event->setProcessedAt($processedAt);
        $event->setProccessed(true);
        $event->setStatus(PaymentStatus::SUCCEEDED);

        // Assert
        $this->assertEquals(2, $event->retryCount);
        $this->assertEquals('Processing failed', $event->errorMessage);
        $this->assertEquals($processedAt, $event->processedAt);
        $this->assertTrue($event->processed);
        $this->assertEquals(PaymentStatus::SUCCEEDED, $event->status);
    }
    #[Test]
    public function test_create_webhook_event_with_null_values()
    {
        // Act
        $event = PaymentEvent::createWebhookEvent(
            null,
            'evt_123',
            null,
            'cs_123',
            null,
            PaymentEventType::WEBHOOK_SESSION_COMPLETED,
            []
        );

        // Assert
        $this->assertNull($event->paymentId);
        $this->assertNull($event->stripePaymentIntentId);
        $this->assertNull($event->amountReceived);
        $this->assertEquals('evt_123', $event->stripeEventId);
        $this->assertEquals('cs_123', $event->stripeSessionId);
    }
    #[Test]
    public function test_reconciliation_event_without_optional_parameters()
    {

        // Act
        $event = PaymentEvent::createReconciliationEvent(
            paymentId: 555,
            outcome: 'success',
            stripeEventId: null,
            stripeSessionId: null,
            eventType: PaymentEventType::RECONCILIATION_FAILED,
            paymentIntentId: null,        );

        // Assert
        $this->assertEquals(555, $event->paymentId);
        $this->assertEquals(['outcome' => 'success'], $event->metadata);
        $this->assertNull($event->amountReceived);
        $this->assertNull($event->status);
        $this->assertTrue($event->processed);
    }
    #[Test]
    public function test_email_event_metadata_structure()
    {
        // Act
        $eventId = "ev_bkbaakdbbj";
        $paymentIntent = "pi_pajppaokdan";
        $sessionId = "cs_aojbdobacbjda";
        $event = PaymentEvent::createEmailEvent(
            666,
            $eventId,
            $paymentIntent,
            $sessionId,
            PaymentEventType::EMAIL_PAYMENT_FAILED,
            'test@example.com',
            ['custom_field' => 'value']
        );

        // Assert - Verificar que todos los campos esperados estén presentes
        $expectedFields = [
            'email_status',
            'recipient_email',
            'initial_status',
            'created_at',
            'attempt_count',
            'last_attempt_at',
            'delivered_at',
            'failed_at',
            'custom_field'
        ];

        foreach ($expectedFields as $field) {
            $this->assertArrayHasKey($field, $event->metadata);
        }

        $this->assertEquals('value', $event->metadata['custom_field']);
        $this->assertEquals('test@example.com', $event->metadata['recipient_email']);
        $this->assertEquals('pending', $event->metadata['initial_status']);
        $this->assertEquals(0, $event->metadata['attempt_count']);
    }
    #[Test]
    public function test_payment_event_constructor_with_all_parameters()
    {
        // Arrange
        $createdAt = new DateTime('2024-01-01 00:00:00');
        $updatedAt = new DateTime('2024-01-01 12:00:00');
        $processedAt = new DateTime('2024-01-01 10:00:00');

        // Act
        $event = new PaymentEvent(
            id: 999,
            paymentId: 888,
            stripeEventId: 'evt_full',
            stripePaymentIntentId: 'pi_full',
            stripeSessionId: 'cs_full',
            eventType: PaymentEventType::WEBHOOK_SESSION_COMPLETED,
            metadata: ['complete' => 'data'],
            amountReceived: '999.99',
            status: PaymentStatus::SUCCEEDED,
            processed: true,
            errorMessage: 'No error',
            retryCount: 1,
            processedAt: $processedAt,
            createdAt: $createdAt,
            updatedAt: $updatedAt
        );

        // Assert
        $this->assertEquals(999, $event->id);
        $this->assertEquals(888, $event->paymentId);
        $this->assertEquals('evt_full', $event->stripeEventId);
        $this->assertEquals('pi_full', $event->stripePaymentIntentId);
        $this->assertEquals('cs_full', $event->stripeSessionId);
        $this->assertEquals(PaymentEventType::WEBHOOK_SESSION_COMPLETED, $event->eventType);
        $this->assertEquals(['complete' => 'data'], $event->metadata);
        $this->assertEquals('999.99', $event->amountReceived);
        $this->assertEquals(PaymentStatus::SUCCEEDED, $event->status);
        $this->assertTrue($event->processed);
        $this->assertEquals('No error', $event->errorMessage);
        $this->assertEquals(1, $event->retryCount);
        $this->assertEquals($processedAt, $event->processedAt);
        $this->assertEquals($createdAt, $event->createdAt);
        $this->assertEquals($updatedAt, $event->updatedAt);
    }

}
