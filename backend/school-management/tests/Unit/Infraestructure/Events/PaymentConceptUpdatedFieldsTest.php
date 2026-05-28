<?php

namespace Tests\Unit\Infraestructure\Events;

use App\Events\PaymentConceptUpdatedFields;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentConceptUpdatedFieldsTest extends TestCase
{
    #[Test]
    public function event_is_created_with_correct_properties(): void
    {
        // Arrange
        $conceptId = 123;
        $changes = [
            'amount' => ['from' => '100.00', 'to' => '150.00'],
            'status' => ['from' => 'active', 'to' => 'inactive'],
        ];

        // Act
        $event = new PaymentConceptUpdatedFields($conceptId, $changes);

        // Assert
        $this->assertEquals($conceptId, $event->conceptId);
        $this->assertEquals($changes, $event->changes);
        $this->assertIsInt($event->conceptId);
        $this->assertIsArray($event->changes);
    }

    #[Test]
    public function event_handles_empty_changes_array(): void
    {
        // Arrange
        $conceptId = 456;
        $changes = [];

        // Act
        $event = new PaymentConceptUpdatedFields($conceptId, $changes);

        // Assert
        $this->assertEquals($conceptId, $event->conceptId);
        $this->assertEmpty($event->changes);
        $this->assertIsArray($event->changes);
    }

    #[Test]
    public function event_handles_complex_changes_structure(): void
    {
        // Arrange
        $conceptId = 789;
        $changes = [
            'amount' => ['from' => '100.00', 'to' => '150.00'],
            'start_date' => ['from' => '2024-01-01', 'to' => '2024-02-01'],
            'end_date' => ['from' => null, 'to' => '2024-12-31'],
            'description' => ['from' => 'Old description', 'to' => 'New description'],
        ];

        // Act
        $event = new PaymentConceptUpdatedFields($conceptId, $changes);

        // Assert
        $this->assertCount(4, $event->changes);
        $this->assertArrayHasKey('amount', $event->changes);
        $this->assertArrayHasKey('start_date', $event->changes);
        $this->assertArrayHasKey('end_date', $event->changes);
        $this->assertArrayHasKey('description', $event->changes);

        // Verificar estructura específica
        $this->assertEquals('100.00', $event->changes['amount']['from']);
        $this->assertEquals('150.00', $event->changes['amount']['to']);
        $this->assertNull($event->changes['end_date']['from']);
        $this->assertEquals('2024-12-31', $event->changes['end_date']['to']);
    }

    #[Test]
    public function event_can_be_dispatched_and_listened(): void
    {
        // Arrange
        $conceptId = 999;
        $changes = ['status' => ['from' => 'draft', 'to' => 'published']];

        $event = new PaymentConceptUpdatedFields($conceptId, $changes);

        // Simular un listener
        $capturedConceptId = null;
        $capturedChanges = null;

        $listener = function(PaymentConceptUpdatedFields $e) use (&$capturedConceptId, &$capturedChanges) {
            $capturedConceptId = $e->conceptId;
            $capturedChanges = $e->changes;
        };

        // Act
        $listener($event);

        // Assert
        $this->assertEquals($conceptId, $capturedConceptId);
        $this->assertEquals($changes, $capturedChanges);
    }

    #[Test]
    public function event_serialization_works_for_queues(): void
    {
        // Arrange
        $conceptId = 111;
        $changes = [
            'amount' => ['from' => '50.00', 'to' => '75.00'],
            'concept_name' => ['from' => 'Old Name', 'to' => 'New Name'],
        ];

        $event = new PaymentConceptUpdatedFields($conceptId, $changes);

        // Act
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        // Assert
        $this->assertInstanceOf(PaymentConceptUpdatedFields::class, $unserialized);
        $this->assertEquals($event->conceptId, $unserialized->conceptId);
        $this->assertEquals($event->changes, $unserialized->changes);
    }

    #[Test]
    public function event_has_required_traits(): void
    {
        // Arrange
        $event = new PaymentConceptUpdatedFields(1, []);

        // Act & Assert
        $traits = class_uses($event);

        $this->assertContains('Illuminate\Foundation\Events\Dispatchable', $traits);
        $this->assertContains('Illuminate\Queue\SerializesModels', $traits);
        $this->assertContains('Illuminate\Broadcasting\InteractsWithSockets', $traits);
    }

}
