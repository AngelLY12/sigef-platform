<?php

namespace Tests\Unit\Infraestructure\Repositories\Command;

use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Infraestructure\Repositories\Command\Payments\EloquentPaymentEventRepository;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\PaymentEvent as EloquentPaymentEvent;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentPaymentEventRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentPaymentEventRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentPaymentEventRepository();
    }

    #[Test]
    public function it_creates_a_payment_event()
    {
        $payment = Payment::factory()->create();
        // Arrange
        $paymentEvent = new PaymentEvent(
            id: null,
            paymentId: $payment->id,
            stripeEventId: 'evt_1MqLX2LkdIwHu7ixY5p5vXvH',
            stripePaymentIntentId: 'pi_3MqLX2LkdIwHu7ix1E8WvXvH',
            stripeSessionId: 'cs_test_a1b2c3d4',
            eventType: PaymentEventType::WEBHOOK_SESSION_COMPLETED,
            metadata: ['source' => 'stripe_webhook'],
            amountReceived: '100.00',
            status: PaymentStatus::SUCCEEDED,
            processed: false,
            errorMessage: null,
            retryCount: 0,
            processedAt: null,
            createdAt: null,
            updatedAt: null,
        );

        // Act
        $result = $this->repository->create($paymentEvent);

        // Assert
        $this->assertInstanceOf(PaymentEvent::class, $result);
        $this->assertNotNull($result->id);
        $this->assertEquals($payment->id, $result->paymentId);
        $this->assertEquals('evt_1MqLX2LkdIwHu7ixY5p5vXvH', $result->stripeEventId);
        $this->assertEquals(PaymentEventType::WEBHOOK_SESSION_COMPLETED, $result->eventType);

        // Verify it was saved in database
        $this->assertDatabaseHas('payment_events', [
            'payment_id' => $payment->id,
            'stripe_event_id' => 'evt_1MqLX2LkdIwHu7ixY5p5vXvH',
            'event_type' => PaymentEventType::WEBHOOK_SESSION_COMPLETED->value,
        ]);
    }

    #[Test]
    public function it_updates_an_existing_payment_event()
    {
        // Arrange
        $paymentEvent = EloquentPaymentEvent::factory()->create([
            'processed' => false,
            'retry_count' => 0,
            'error_message' => null,
        ]);

        $updateData = [
            'processed' => true,
            'retry_count' => 1,
            'error_message' => 'Processing error',
            'processed_at' => now(),
        ];

        // Act
        $result = $this->repository->update($paymentEvent->id, $updateData);

        // Assert
        $this->assertInstanceOf(PaymentEvent::class, $result);
        $this->assertTrue($result->processed);
        $this->assertEquals(1, $result->retryCount);
        $this->assertEquals('Processing error', $result->errorMessage);
        $this->assertNotNull($result->processedAt);

        // Verify database update
        $this->assertDatabaseHas('payment_events', [
            'id' => $paymentEvent->id,
            'processed' => true,
            'retry_count' => 1,
            'error_message' => 'Processing error',
        ]);
    }

    #[Test]
    public function it_throws_exception_when_updating_non_existent_event()
    {
        // Arrange
        $nonExistentId = 999999;
        $updateData = ['processed' => true];

        // Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $this->repository->update($nonExistentId, $updateData);
    }

    #[Test]
    public function it_can_update_partial_fields()
    {
        // Arrange
        $paymentEvent = EloquentPaymentEvent::factory()->create([
            'processed' => false,
            'error_message' => null,
        ]);

        $updateData = ['processed' => true];

        // Act
        $result = $this->repository->update($paymentEvent->id, $updateData);

        // Assert
        $this->assertTrue($result->processed);

        // Other fields should remain unchanged
        $this->assertEquals($paymentEvent->retry_count, $result->retryCount);
        $this->assertEquals($paymentEvent->error_message, $result->errorMessage);
    }

    #[Test]
    public function it_can_update_status_field()
    {
        // Arrange
        $paymentEvent = EloquentPaymentEvent::factory()->create([
            'status' => PaymentStatus::DEFAULT->value,
        ]);

        $updateData = ['status' => PaymentStatus::SUCCEEDED->value];

        // Act
        $result = $this->repository->update($paymentEvent->id, $updateData);

        // Assert
        $this->assertEquals(PaymentStatus::SUCCEEDED, $result->status);
        $this->assertDatabaseHas('payment_events', [
            'id' => $paymentEvent->id,
            'status' => PaymentStatus::SUCCEEDED->value,
        ]);
    }

    #[Test]
    public function it_can_update_event_type_field()
    {
        // Arrange
        $paymentEvent = EloquentPaymentEvent::factory()->create([
            'event_type' => PaymentEventType::WEBHOOK_SESSION_COMPLETED->value,
        ]);

        $updateData = ['event_type' => PaymentEventType::WEBHOOK_SESSION_COMPLETED->value];

        // Act
        $result = $this->repository->update($paymentEvent->id, $updateData);

        // Assert
        $this->assertEquals(PaymentEventType::WEBHOOK_SESSION_COMPLETED, $result->eventType);
    }

    #[Test]
    public function it_refreshes_the_model_after_update()
    {
        // Arrange
        $paymentEvent = EloquentPaymentEvent::factory()->create([
            'processed' => false,
            'updated_at' => now()->subDay(),
        ]);

        $originalUpdatedAt = $paymentEvent->updated_at;

        $updateData = ['processed' => true];

        // Act
        $result = $this->repository->update($paymentEvent->id, $updateData);

        // Assert
        $this->assertNotEquals($originalUpdatedAt, $result->updatedAt);
        $this->assertGreaterThan($originalUpdatedAt, $result->updatedAt);
    }

}
