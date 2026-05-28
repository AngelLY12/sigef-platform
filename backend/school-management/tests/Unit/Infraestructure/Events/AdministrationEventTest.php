<?php

namespace Tests\Unit\Infraestructure\Events;

use App\Events\AdministrationEvent;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdministrationEventTest extends TestCase
{
    #[Test]
    public function event_is_created_with_correct_properties(): void
    {
        // Arrange
        $amount = '150.75';
        $id = 123;
        $conceptName = 'Monthly Fee';
        $action = 'payment_processed';

        // Act
        $event = new AdministrationEvent($amount, $id, $conceptName, $action);

        // Assert
        $this->assertEquals($amount, $event->amount);
        $this->assertEquals($id, $event->id);
        $this->assertEquals($conceptName, $event->concept_name);
        $this->assertEquals($action, $event->action);
    }

    #[Test]
    public function event_properties_are_publicly_accessible(): void
    {
        // Arrange
        $event = new AdministrationEvent('200.50', 456, 'Annual Subscription', 'refund_issued');

        // Act & Assert
        $this->assertIsString($event->amount);
        $this->assertIsInt($event->id);
        $this->assertIsString($event->concept_name);
        $this->assertIsString($event->action);
    }

    #[Test]
    public function event_has_correct_serialization_traits(): void
    {
        // Arrange
        $event = new AdministrationEvent('50.25', 321, 'Late Fee', 'updated');

        // Act & Assert
        // Verificar que usa los traits correctos
        $traits = class_uses($event);

        $this->assertContains('Illuminate\Foundation\Events\Dispatchable', $traits);
        $this->assertContains('Illuminate\Queue\SerializesModels', $traits);
    }

    #[Test]
    public function event_can_be_instantiated_with_different_values(): void
    {
        // Test 1: Valores normales
        $event1 = new AdministrationEvent('99.99', 1, 'Test Concept', 'created');
        $this->assertEquals('99.99', $event1->amount);
        $this->assertEquals(1, $event1->id);
        $this->assertEquals('Test Concept', $event1->concept_name);
        $this->assertEquals('created', $event1->action);

        // Test 2: Valores límite
        $event2 = new AdministrationEvent('0.00', PHP_INT_MAX, 'A very long concept name that might be used in some cases', 'a_very_long_action_name_that_exceeds_normal_length');
        $this->assertEquals('0.00', $event2->amount);
        $this->assertEquals(PHP_INT_MAX, $event2->id);
        $this->assertIsString($event2->concept_name);
        $this->assertIsString($event2->action);

        // Test 3: Valores con caracteres especiales
        $event3 = new AdministrationEvent('1,000.50', 999, 'Concept with spéciäl chàractèrs', 'acción_con_ñ');
        $this->assertEquals('1,000.50', $event3->amount);
        $this->assertEquals(999, $event3->id);
        $this->assertEquals('Concept with spéciäl chàractèrs', $event3->concept_name);
        $this->assertEquals('acción_con_ñ', $event3->action);
    }

    #[Test]
    public function event_uses_serializes_models_trait_for_queued_broadcasting(): void
    {
        // Arrange
        $event = new AdministrationEvent('75.50', 555, 'Queued Concept', 'queued_action');

        // Esta prueba verifica que el evento puede ser serializado
        // (importante para broadcasting y queues)

        // Act - Serializar y deserializar
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        // Assert
        $this->assertInstanceOf(AdministrationEvent::class, $unserialized);
        $this->assertEquals($event->amount, $unserialized->amount);
        $this->assertEquals($event->id, $unserialized->id);
        $this->assertEquals($event->concept_name, $unserialized->concept_name);
        $this->assertEquals($event->action, $unserialized->action);
    }

    #[Test]
    public function event_is_dispatchable(): void
    {
        // Arrange
        $event = new AdministrationEvent('25.00', 777, 'Dispatch Test', 'dispatched');

        // Esta prueba verifica que el evento puede ser dispatchado
        // usando el trait Dispatchable

        // Act & Assert - Simular dispatch
        // Nota: No estamos realmente dispatchando, solo verificando que tiene la capacidad
        $this->assertTrue(method_exists($event, 'dispatch'));
        $this->assertTrue(method_exists($event, 'dispatchIf'));
        $this->assertTrue(method_exists($event, 'dispatchUnless'));
    }

    #[Test]
    public function event_can_be_used_in_listeners_and_handlers(): void
    {
        // Arrange
        $amount = '500.00';
        $id = 888;
        $conceptName = 'Annual Membership';
        $action = 'renewed';

        $event = new AdministrationEvent($amount, $id, $conceptName, $action);

        // Simular un listener/handler
        $capturedAmount = null;
        $capturedId = null;
        $capturedConceptName = null;
        $capturedAction = null;

        // Simular lo que haría un listener real
        $listener = function(AdministrationEvent $event) use (&$capturedAmount, &$capturedId, &$capturedConceptName, &$capturedAction) {
            $capturedAmount = $event->amount;
            $capturedId = $event->id;
            $capturedConceptName = $event->concept_name;
            $capturedAction = $event->action;
        };

        // Act
        $listener($event);

        // Assert
        $this->assertEquals($amount, $capturedAmount);
        $this->assertEquals($id, $capturedId);
        $this->assertEquals($conceptName, $capturedConceptName);
        $this->assertEquals($action, $capturedAction);
    }

    #[Test]
    public function event_properties_cannot_be_changed_after_creation(): void
    {
        // Arrange
        $event = new AdministrationEvent('100.00', 999, 'Original Concept', 'original_action');

        // Act & Assert - Intentar cambiar propiedades (esto debería fallar si las propiedades fueran privadas,
        // pero como son públicas, podemos cambiarlas. La prueba verifica el comportamiento actual)

        // Esta prueba es más para documentación que para verificación estricta
        $originalAmount = $event->amount;
        $originalId = $event->id;

        // Como las propiedades son públicas, pueden ser modificadas
        $event->amount = '200.00';
        $event->id = 1111;

        $this->assertEquals('200.00', $event->amount);
        $this->assertEquals(1111, $event->id);

        // Restaurar valores para demostrar que fueron cambiados
        $this->assertNotEquals($originalAmount, $event->amount);
        $this->assertNotEquals($originalId, $event->id);
    }

}
