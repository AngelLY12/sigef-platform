<?php

namespace Tests\Unit\Infraestructure\Repositories\Query;

use App\Models\Payment;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Core\Domain\Entities\PaymentEvent;
use App\Core\Domain\Enum\Payment\PaymentEventType;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Infraestructure\Repositories\Query\Payments\EloquentPaymentEventQueryRepository;
use App\Models\PaymentEvent as EloquentPaymentEvent;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EloquentPaymentEventQueryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentPaymentEventQueryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentPaymentEventQueryRepository();
    }

    #[Test]
    public function it_finds_payment_event_by_id()
    {
        // Arrange
        $paymentEvent = EloquentPaymentEvent::factory()->create();

        // Act
        $result = $this->repository->findById($paymentEvent->id);

        // Assert
        $this->assertInstanceOf(PaymentEvent::class, $result);
        $this->assertEquals($paymentEvent->id, $result->id);
        $this->assertEquals($paymentEvent->payment_id, $result->paymentId);
    }

    #[Test]
    public function it_returns_null_when_finding_non_existent_id()
    {
        // Act
        $result = $this->repository->findById(999999);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function it_finds_payment_event_by_payment_id()
    {
        // Arrange
        $paymentId = Payment::factory()->create()->id;
        $paymentEvent = EloquentPaymentEvent::factory()->create(['payment_id' => $paymentId]);

        // Act
        $result = $this->repository->findByPaymentId($paymentId);

        // Assert
        $this->assertInstanceOf(PaymentEvent::class, $result);
        $this->assertEquals($paymentId, $result->paymentId);
        $this->assertEquals($paymentEvent->id, $result->id);
    }

    #[Test]
    public function it_returns_null_when_no_event_for_payment_id()
    {
        // Act
        $result = $this->repository->findByPaymentId(999999);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function it_finds_all_events_by_payment_id_ordered_by_created_at_desc()
    {
        // Arrange
        $paymentId = Payment::factory()->create()->id;

        $event1 = EloquentPaymentEvent::factory()->create([
            'payment_id' => $paymentId,
            'created_at' => now()->subDays(2),
        ]);

        $event2 = EloquentPaymentEvent::factory()->create([
            'payment_id' => $paymentId,
            'created_at' => now()->subDays(1),
        ]);

        $event3 = EloquentPaymentEvent::factory()->create([
            'payment_id' => $paymentId,
            'created_at' => now(),
        ]);

        // Act
        $result = $this->repository->findAllEventsByPaymentId($paymentId);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        // Verify order is correct (newest first)
        $this->assertEquals($event3->id, $result[0]->id);
        $this->assertEquals($event2->id, $result[1]->id);
        $this->assertEquals($event1->id, $result[2]->id);
    }

    #[Test]
    public function it_returns_empty_array_when_no_events_for_payment()
    {
        // Act
        $result = $this->repository->findAllEventsByPaymentId(999999);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_finds_payment_event_by_payment_intent_id()
    {
        // Arrange
        $paymentIntentId = 'pi_123456789';
        $paymentEvent = EloquentPaymentEvent::factory()->create([
            'stripe_payment_intent_id' => $paymentIntentId,
        ]);

        // Act
        $result = $this->repository->findByPaymentIntentId($paymentIntentId);

        // Assert
        $this->assertInstanceOf(PaymentEvent::class, $result);
        $this->assertEquals($paymentIntentId, $result->stripePaymentIntentId);
    }

    #[Test]
    public function it_finds_payment_event_by_stripe_session_id()
    {
        // Arrange
        $sessionId = 'cs_123456789';
        $paymentEvent = EloquentPaymentEvent::factory()->create([
            'stripe_session_id' => $sessionId,
        ]);

        // Act
        $result = $this->repository->findByStripeSessionId($sessionId);

        // Assert
        $this->assertInstanceOf(PaymentEvent::class, $result);
        $this->assertEquals($sessionId, $result->stripeSessionId);
    }

    #[Test]
    public function it_finds_payment_event_by_stripe_event_id_and_type()
    {
        // Arrange
        $stripeEventId = 'evt_123456789';
        $eventType = PaymentEventType::WEBHOOK_PAYMENT_SUCCEEDED;

        $paymentEvent = EloquentPaymentEvent::factory()->create([
            'stripe_event_id' => $stripeEventId,
            'event_type' => $eventType,
        ]);

        // Act
        $result = $this->repository->findByStripeEvent($stripeEventId, $eventType);

        // Assert
        $this->assertInstanceOf(PaymentEvent::class, $result);
        $this->assertEquals($stripeEventId, $result->stripeEventId);
        $this->assertEquals($eventType, $result->eventType);
    }

    #[Test]
    public function it_finds_pending_events()
    {
        // Arrange - Create pending events
        $pendingEvents = EloquentPaymentEvent::factory()->count(3)->create([
            'processed' => false,
            'retry_count' => 0,
            'created_at' => now()->subHours(12),
        ]);

        // Create non-pending events that shouldn't be returned
        EloquentPaymentEvent::factory()->create(['processed' => true]); // Already processed
        EloquentPaymentEvent::factory()->create(['retry_count' => 3]); // Max retries
        EloquentPaymentEvent::factory()->create(['created_at' => now()->subHours(25)]); // Too old

        // Act
        $result = $this->repository->findPendingEvents();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        foreach ($result as $event) {
            $this->assertFalse($event->processed);
            $this->assertLessThan(3, $event->retryCount);
        }
    }

    #[Test]
    public function it_finds_pending_events_with_custom_parameters()
    {
        // Arrange
        EloquentPaymentEvent::factory()->create([
            'processed' => false,
            'retry_count' => 2,
            'created_at' => now()->subHours(12),
        ]);

        // Act - With custom maxRetries = 1 (this event has retry_count = 2, so it shouldn't be included)
        $result = $this->repository->findPendingEvents(24, 1);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_checks_existence_by_stripe_event_and_type()
    {
        // Arrange
        $stripeEventId = 'evt_123456789';
        $eventType = PaymentEventType::WEBHOOK_PAYMENT_SUCCEEDED;

        EloquentPaymentEvent::factory()->create([
            'stripe_event_id' => $stripeEventId,
            'event_type' => $eventType,
        ]);

        // Act & Assert
        $this->assertTrue($this->repository->existsByStripeEvent($stripeEventId, $eventType));
        $this->assertFalse($this->repository->existsByStripeEvent('non_existent', $eventType));
        $this->assertTrue($this->repository->existsByStripeEvent($stripeEventId, PaymentEventType::WEBHOOK_PAYMENT_SUCCEEDED));
    }

    #[Test]
    public function it_finds_recent_reconciliation_event()
    {
        // Arrange
        $paymentId = Payment::factory()->create()->id;
        $since = now()->subDay();

        $recentEvent = EloquentPaymentEvent::factory()->create([
            'payment_id' => $paymentId,
            'event_type' => PaymentEventType::RECONCILIATION_COMPLETED,
            'created_at' => now()->subHours(1),
        ]);

        // Old event that shouldn't be returned
        EloquentPaymentEvent::factory()->create([
            'payment_id' => $paymentId,
            'event_type' => PaymentEventType::RECONCILIATION_COMPLETED,
            'created_at' => now()->subDays(2),
        ]);

        // Non-reconciliation event that shouldn't be returned
        EloquentPaymentEvent::factory()->create([
            'payment_id' => $paymentId,
            'event_type' => PaymentEventType::WEBHOOK_PAYMENT_SUCCEEDED,
            'created_at' => now()->subHours(1),
        ]);

        // Act
        $result = $this->repository->findRecentReconciliationEvent($paymentId, $since);

        // Assert
        $this->assertInstanceOf(PaymentEvent::class, $result);
        $this->assertEquals($recentEvent->id, $result->id);
        $this->assertEquals(PaymentEventType::RECONCILIATION_COMPLETED, $result->eventType);
    }

    #[Test]
    public function it_finds_all_reconciliation_events_for_payment()
    {
        // Arrange
        $paymentId = Payment::factory()->create()->id;

        $reconciliationEvents = [
            PaymentEventType::RECONCILIATION_STARTED,
            PaymentEventType::RECONCILIATION_COMPLETED,
            PaymentEventType::RECONCILIATION_FAILED,
        ];

        foreach ($reconciliationEvents as $eventType) {
            EloquentPaymentEvent::factory()->create([
                'payment_id' => $paymentId,
                'event_type' => $eventType,
            ]);
        }

        // Non-reconciliation event that shouldn't be included
        EloquentPaymentEvent::factory()->create([
            'payment_id' => $paymentId,
            'event_type' => PaymentEventType::WEBHOOK_SESSION_COMPLETED,
        ]);

        // Act
        $result = $this->repository->findReconciliationEventsByPayment($paymentId);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        $eventTypes = array_map(fn($event) => $event->eventType, $result);
        $this->assertContains(PaymentEventType::RECONCILIATION_STARTED, $eventTypes);
        $this->assertContains(PaymentEventType::RECONCILIATION_COMPLETED, $eventTypes);
        $this->assertContains(PaymentEventType::RECONCILIATION_FAILED, $eventTypes);
    }

    #[Test]
    public function it_gets_payments_needing_reconciliation()
    {
        $payment1 = Payment::factory()->create([
            'status' => PaymentStatus::PAID, //
            'payment_intent_id' => 'pi_test_1_' . Str::random(10),
            'created_at' => now()->subDays(10),
        ]);

        // Payment 2: Similar
        $payment2 = Payment::factory()->create([
            'status' => PaymentStatus::PAID,
            'payment_intent_id' => 'pi_test_2_' . Str::random(10),
            'created_at' => now()->subDays(15),
        ]);

        // Payment 3: Similar
        $payment3 = Payment::factory()->create([
            'status' => PaymentStatus::PAID,
            'payment_intent_id' => 'pi_test_3_' . Str::random(10),
            'created_at' => now()->subDays(20),
        ]);

        // Payment 1: Has events but no recent reconciliation
        EloquentPaymentEvent::factory()->forPayment($payment1->id)->create([
            'event_type' => PaymentEventType::WEBHOOK_PAYMENT_SUCCEEDED,
        ]);

        // Payment 2: Has events with recent reconciliation (should be excluded)
        EloquentPaymentEvent::factory()->forPayment($payment2->id)->create([
            'event_type' => PaymentEventType::WEBHOOK_PAYMENT_SUCCEEDED,
        ]);
        EloquentPaymentEvent::factory()->forPayment($payment2->id)->create([
            'event_type' => PaymentEventType::RECONCILIATION_COMPLETED,
            'created_at' => now()->subHour(),
        ]);

        EloquentPaymentEvent::factory()->forPayment($payment3->id)->create([
            'event_type' => PaymentEventType::WEBHOOK_PAYMENT_SUCCEEDED,
        ]);
        EloquentPaymentEvent::factory()->forPayment($payment3->id)->create([
            'event_type' => PaymentEventType::RECONCILIATION_FAILED,
            'created_at' => now()->subMinutes(30),
        ]);

        // Act
        $result = $this->repository->getPaymentsNeedingReconciliation();

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContains($payment1->id, $result);
        $this->assertNotContains($payment2->id, $result);
        $this->assertContains($payment3->id, $result);
    }

    #[Test]
    public function it_returns_empty_array_when_no_payments_need_reconciliation()
    {
        // Arrange
        EloquentPaymentEvent::factory()->create(['payment_id' => null]); // Should be ignored

        // Act
        $result = $this->repository->getPaymentsNeedingReconciliation();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_excludes_payments_with_recent_reconciliation_from_needing_reconciliation()
    {
        // Arrange
        $payment = Payment::factory()->create([
            'status' => PaymentStatus::PAID,
            'payment_intent_id' => 'pi_test_' . Str::random(10),
            'created_at' => now()->subDays(5),
        ]);


        // Create payment with old reconciliation (should be included)
        EloquentPaymentEvent::factory()->forPayment($payment->id)->create([
            'event_type' => PaymentEventType::WEBHOOK_PAYMENT_SUCCEEDED,
        ]);
        EloquentPaymentEvent::factory()->forPayment($payment->id)->create([
            'event_type' => PaymentEventType::RECONCILIATION_COMPLETED,
            'created_at' => now()->subHours(3), // Older than 2 hours
        ]);

        // Act
        $result = $this->repository->getPaymentsNeedingReconciliation();

        // Assert
        $this->assertContains($payment->id, $result);
    }

    #[Test]
    public function it_handles_find_by_payment_id_when_multiple_events_exist()
    {
        // Arrange
        $paymentId = Payment::factory()->create()->id;

        // Create multiple events for same payment
        EloquentPaymentEvent::factory()->count(3)->create(['payment_id' => $paymentId]);

        // Act
        $result = $this->repository->findByPaymentId($paymentId);

        // Assert - Should return the first one (as per ->first())
        $this->assertInstanceOf(PaymentEvent::class, $result);
        $this->assertEquals($paymentId, $result->paymentId);
    }

    #[Test]
    public function it_returns_null_for_find_by_stripe_event_when_no_match()
    {
        // Act
        $result = $this->repository->findByStripeEvent(
            'non_existent',
            PaymentEventType::WEBHOOK_PAYMENT_SUCCEEDED
        );

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function it_orders_pending_events_by_created_at_asc()
    {
        // Arrange
        $olderEvent = EloquentPaymentEvent::factory()->create([
            'processed' => false,
            'created_at' => now()->subHours(10),
        ]);

        $newerEvent = EloquentPaymentEvent::factory()->create([
            'processed' => false,
            'created_at' => now()->subHours(5),
        ]);

        // Act
        $result = $this->repository->findPendingEvents();

        // Assert - Should be ordered by created_at asc (oldest first)
        $this->assertCount(2, $result);
        $this->assertEquals($olderEvent->id, $result[0]->id);
        $this->assertEquals($newerEvent->id, $result[1]->id);
    }

    #[Test]
    public function it_checks_existence_by_payment_id_and_event_type(): void
    {
        // Arrange
        $paymentId = Payment::factory()->create()->id;
        $eventType = PaymentEventType::RECONCILIATION_COMPLETED;

        EloquentPaymentEvent::factory()->create([
            'payment_id' => $paymentId,
            'event_type' => $eventType,
        ]);

        // Act & Assert
        $this->assertTrue($this->repository->existsByPaymentId($paymentId, $eventType));
        $this->assertFalse($this->repository->existsByPaymentId(999999, $eventType));
        $this->assertFalse($this->repository->existsByPaymentId($paymentId, PaymentEventType::RECONCILIATION_FAILED));
    }

    #[Test]
    public function it_checks_existence_by_payment_intent_id_and_event_type(): void
    {
        // Arrange
        $paymentIntentId = 'pi_123456789';
        $eventType = PaymentEventType::SYSTEM_CORRECTION;

        EloquentPaymentEvent::factory()->create([
            'stripe_payment_intent_id' => $paymentIntentId,
            'event_type' => $eventType,
        ]);

        // Act & Assert
        $this->assertTrue($this->repository->existsByPaymentIntentId($paymentIntentId, $eventType));
        $this->assertFalse($this->repository->existsByPaymentIntentId('non_existent', $eventType));
        $this->assertFalse($this->repository->existsByPaymentIntentId($paymentIntentId, PaymentEventType::RECONCILIATION_COMPLETED));
    }

    #[Test]
    public function it_returns_false_for_exists_by_payment_id_when_no_matching_event_type(): void
    {
        // Arrange
        $paymentId = Payment::factory()->create()->id;

        // Create an event with different type
        EloquentPaymentEvent::factory()->create([
            'payment_id' => $paymentId,
            'event_type' => PaymentEventType::WEBHOOK_PAYMENT_SUCCEEDED,
        ]);

        // Act & Assert
        $this->assertFalse($this->repository->existsByPaymentId($paymentId, PaymentEventType::SYSTEM_CORRECTION));
    }

    #[Test]
    public function it_returns_false_for_exists_by_payment_intent_id_when_no_matching_event_type(): void
    {
        // Arrange
        $paymentIntentId = 'pi_123456789';

        EloquentPaymentEvent::factory()->create([
            'stripe_payment_intent_id' => $paymentIntentId,
            'event_type' => PaymentEventType::WEBHOOK_PAYMENT_SUCCEEDED,
        ]);

        // Act & Assert
        $this->assertFalse($this->repository->existsByPaymentIntentId($paymentIntentId, PaymentEventType::SYSTEM_CORRECTION));
    }

}
