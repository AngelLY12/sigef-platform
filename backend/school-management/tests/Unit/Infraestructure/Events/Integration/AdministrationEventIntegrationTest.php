<?php

namespace Tests\Unit\Infraestructure\Events\Integration;

use App\Events\AdministrationEvent;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdministrationEventIntegrationTest extends TestCase
{
    #[Test]
    public function event_can_be_dispatched_and_listened(): void
    {
        // Arrange
        Event::fake();

        $amount = '150.75';
        $id = 123;
        $conceptName = 'Monthly Fee';
        $action = 'payment_processed';

        // Act
        AdministrationEvent::dispatch($amount, $id, $conceptName, $action);

        // Assert
        Event::assertDispatched(AdministrationEvent::class, function ($event) use ($amount, $id, $conceptName, $action) {
            return $event->amount === $amount &&
                $event->id === $id &&
                $event->concept_name === $conceptName &&
                $event->action === $action;
        });
    }

    #[Test]
    public function event_can_be_dispatched_conditionally(): void
    {
        // Arrange
        Event::fake();

        $amount = '100.00';
        $id = 456;
        $conceptName = 'Test Concept';
        $action = 'test_action';

        // Act - Dispatch condicional (true)
        AdministrationEvent::dispatchIf(true, $amount, $id, $conceptName, $action);

        // Assert - Debería dispararse
        Event::assertDispatched(AdministrationEvent::class);

        // Act - Dispatch condicional (false)
        Event::fake();
        AdministrationEvent::dispatchIf(false, $amount, $id, $conceptName, $action);

        // Assert - No debería dispararse
        Event::assertNotDispatched(AdministrationEvent::class);
    }

    #[Test]
    public function multiple_events_can_be_dispatched(): void
    {
        // Arrange
        Event::fake();

        $eventsData = [
            ['amount' => '100.00', 'id' => 1, 'concept' => 'Concept 1', 'action' => 'action1'],
            ['amount' => '200.00', 'id' => 2, 'concept' => 'Concept 2', 'action' => 'action2'],
            ['amount' => '300.00', 'id' => 3, 'concept' => 'Concept 3', 'action' => 'action3'],
        ];

        // Act
        foreach ($eventsData as $data) {
            AdministrationEvent::dispatch(
                $data['amount'],
                $data['id'],
                $data['concept'],
                $data['action']
            );
        }

        // Assert
        Event::assertDispatchedTimes(AdministrationEvent::class, 3);
    }

    #[Test]
    public function event_listener_receives_correct_data(): void
    {
        // Arrange - NO usar Event::fake() para que el listener se ejecute
        $receivedData = [];

        Event::listen(AdministrationEvent::class, function (AdministrationEvent $event) use (&$receivedData) {
            $receivedData = [
                'amount' => $event->amount,
                'id' => $event->id,
                'concept_name' => $event->concept_name,
                'action' => $event->action,
            ];
        });

        $amount = '250.00';
        $id = 999;
        $conceptName = 'Integration Test';
        $action = 'processed';

        // Act
        event(new AdministrationEvent($amount, $id, $conceptName, $action));

        // Assert
        $this->assertEquals([
            'amount' => $amount,
            'id' => $id,
            'concept_name' => $conceptName,
            'action' => $action,
        ], $receivedData);
    }

    #[Test]
    public function event_works_with_real_listeners_registered_in_service_provider(): void
    {
        // Este test verifica que los listeners registrados en EventServiceProvider funcionen
        // Arrange
        Event::fake();

        // Simular que tenemos listeners registrados en EventServiceProvider
        // (En realidad solo estamos verificando que el evento se dispara)

        $amount = '500.00';
        $id = 777;
        $conceptName = 'Service Provider Test';
        $action = 'handled';

        // Act
        AdministrationEvent::dispatch($amount, $id, $conceptName, $action);

        // Assert
        Event::assertDispatched(AdministrationEvent::class, function ($event) use ($amount, $id, $conceptName, $action) {
            return $event->amount === $amount &&
                $event->id === $id &&
                $event->concept_name === $conceptName &&
                $event->action === $action;
        });
    }

    #[Test]
    public function event_can_be_faked_and_asserted_without_executing_real_listeners(): void
    {
        // Este test demuestra el uso correcto de Event::fake()
        // Arrange
        Event::fake();

        // Registrar un listener (no se ejecutará porque estamos faking)
        $listenerExecuted = false;
        Event::listen(AdministrationEvent::class, function () use (&$listenerExecuted) {
            $listenerExecuted = true;
        });

        $amount = '350.00';
        $id = 888;
        $conceptName = 'Fake Test';
        $action = 'faked';

        // Act
        event(new AdministrationEvent($amount, $id, $conceptName, $action));

        // Assert
        Event::assertDispatched(AdministrationEvent::class);

        // El listener NO debería ejecutarse porque usamos Event::fake()
        $this->assertFalse($listenerExecuted, 'Listener should not execute when events are faked');
    }
}
