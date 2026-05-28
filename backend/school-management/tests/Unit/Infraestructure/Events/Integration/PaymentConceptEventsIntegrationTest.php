<?php

namespace Tests\Unit\Infraestructure\Events\Integration;

use App\Events\PaymentConceptUpdatedFields;
use App\Events\PaymentConceptUpdatedRelations;
use App\Models\PaymentConcept;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentConceptEventsIntegrationTest extends TestCase
{
    #[Test]
    public function payment_concept_updated_fields_event_can_be_dispatched(): void
    {
        // Arrange
        Event::fake();

        $conceptId = 123;
        $changes = [
            'amount' => ['from' => '100.00', 'to' => '150.00'],
            'status' => ['from' => 'active', 'to' => 'inactive'],
        ];

        // Act
        PaymentConceptUpdatedFields::dispatch($conceptId, $changes);

        // Assert
        Event::assertDispatched(PaymentConceptUpdatedFields::class, function ($event) use ($conceptId, $changes) {
            return $event->conceptId === $conceptId &&
                $event->changes === $changes;
        });
    }

    #[Test]
    public function payment_concept_updated_relations_event_can_be_dispatched(): void
    {
        // Arrange
        Event::fake();

        $newPaymentConceptId = 456;
        $oldPaymentConceptArray = ['id' => 456, 'applies_to' => 'ALL_STUDENTS'];
        $dtoArray = ['id' => 456, 'applies_to' => 'BY_CAREER', 'careers' => [1, 2]];
        $appliesTo = 'BY_CAREER';
        $oldRecipientIds = [1, 2, 3, 4, 5];

        // Act
        PaymentConceptUpdatedRelations::dispatch(
            $newPaymentConceptId,
            $oldPaymentConceptArray,
            $dtoArray,
            $appliesTo,
            $oldRecipientIds
        );

        // Assert
        Event::assertDispatched(PaymentConceptUpdatedRelations::class, function ($event) use ($newPaymentConceptId, $appliesTo) {
            return $event->newPaymentConceptId === $newPaymentConceptId &&
                $event->appliesTo === $appliesTo;
        });
    }

    #[Test]
    public function both_events_can_be_dispatched_together(): void
    {
        // Arrange
        Event::fake();

        // Simular una actualización completa que dispara ambos eventos
        $conceptId = 789;

        // Datos para el evento de campos
        $fieldChanges = [
            'amount' => ['from' => '200.00', 'to' => '250.00'],
            'concept_name' => ['from' => 'Old Name', 'to' => 'New Name'],
        ];

        // Datos para el evento de relaciones
        $oldPaymentConceptArray = ['id' => 789, 'applies_to' => 'ALL_STUDENTS'];
        $dtoArray = ['id' => 789, 'applies_to' => 'SPECIFIC_STUDENTS', 'students' => [1, 2, 3]];
        $appliesTo = 'SPECIFIC_STUDENTS';
        $oldRecipientIds = range(1, 100);

        // Act
        PaymentConceptUpdatedFields::dispatch($conceptId, $fieldChanges);
        PaymentConceptUpdatedRelations::dispatch(
            $conceptId,
            $oldPaymentConceptArray,
            $dtoArray,
            $appliesTo,
            $oldRecipientIds
        );

        // Assert
        Event::assertDispatched(PaymentConceptUpdatedFields::class);
        Event::assertDispatched(PaymentConceptUpdatedRelations::class);
        Event::assertDispatchedTimes(PaymentConceptUpdatedFields::class, 1);
        Event::assertDispatchedTimes(PaymentConceptUpdatedRelations::class, 1);
    }

    #[Test]
    public function events_can_have_multiple_listeners(): void
    {
        // Arrange - NO usar Event::fake() aquí para que los listeners se ejecuten
        $fieldsListener1Called = false;
        $fieldsListener2Called = false;
        $relationsListener1Called = false;
        $relationsListener2Called = false;

        Event::listen(PaymentConceptUpdatedFields::class, function () use (&$fieldsListener1Called) {
            $fieldsListener1Called = true;
        });

        Event::listen(PaymentConceptUpdatedFields::class, function () use (&$fieldsListener2Called) {
            $fieldsListener2Called = true;
        });

        Event::listen(PaymentConceptUpdatedRelations::class, function () use (&$relationsListener1Called) {
            $relationsListener1Called = true;
        });

        Event::listen(PaymentConceptUpdatedRelations::class, function () use (&$relationsListener2Called) {
            $relationsListener2Called = true;
        });

        $concept1= PaymentConcept::factory()->create();
        $concept2= PaymentConcept::factory()->create();

        // Act
        event(new PaymentConceptUpdatedFields($concept1->id, []));
        event(new PaymentConceptUpdatedRelations($concept2->id, [], [], 'ALL_STUDENTS', []));

        // Assert
        $this->assertTrue($fieldsListener1Called, 'Fields listener 1 should be called');
        $this->assertTrue($fieldsListener2Called, 'Fields listener 2 should be called');
        $this->assertTrue($relationsListener1Called, 'Relations listener 1 should be called');
        $this->assertTrue($relationsListener2Called, 'Relations listener 2 should be called');

        // Limpiar listeners después del test
        Event::forget(PaymentConceptUpdatedFields::class);
        Event::forget(PaymentConceptUpdatedRelations::class);
    }

    #[Test]
    public function events_can_be_faked_and_verified_without_executing_listeners(): void
    {
        // Este test demuestra el uso CORRECTO de Event::fake()
        // Arrange
        Event::fake();

        // Registrar listeners (NO se ejecutarán debido a Event::fake())
        $listenerExecuted = false;
        Event::listen(PaymentConceptUpdatedFields::class, function () use (&$listenerExecuted) {
            $listenerExecuted = true;
        });

        // Act
        event(new PaymentConceptUpdatedFields(333, ['test' => ['from' => 'a', 'to' => 'b']]));

        // Assert usando los métodos de aserción de Event::fake()
        Event::assertDispatched(PaymentConceptUpdatedFields::class);
        Event::assertDispatched(PaymentConceptUpdatedFields::class, function ($event) {
            return $event->conceptId === 333;
        });

        // Verificar que el listener NO se ejecutó (porque usamos Event::fake())
        $this->assertFalse($listenerExecuted, 'Listener should not execute when events are faked');
    }

    protected function tearDown(): void
    {
        // Limpiar todos los listeners registrados durante los tests
        Event::forget(PaymentConceptUpdatedFields::class);
        Event::forget(PaymentConceptUpdatedRelations::class);
        parent::tearDown();
    }

    #[Test]
    public function events_can_be_used_in_real_workflow(): void
    {
        // Arrange
        Event::fake();

        // Simular un flujo real de actualización de concepto de pago
        $conceptId = 999;

        // Paso 1: Actualizar campos básicos
        $fieldChanges = [
            'amount' => ['from' => '300.00', 'to' => '350.00'],
            'description' => ['from' => null, 'to' => 'Updated description for 2024'],
        ];

        PaymentConceptUpdatedFields::dispatch($conceptId, $fieldChanges);

        // Paso 2: Actualizar relaciones (ej: cambiar de todos los estudiantes a carreras específicas)
        $oldPaymentConceptArray = [
            'id' => $conceptId,
            'applies_to' => 'ALL_STUDENTS',
            'user_ids' => [],
            'career_ids' => [],
        ];

        $dtoArray = [
            'id' => $conceptId,
            'applies_to' => 'BY_CAREER',
            'careers' => [1, 2, 3],
            'semesters' => ['2024-1'],
            'replace_relations' => true,
        ];

        $appliesTo = 'BY_CAREER';
        $oldRecipientIds = range(1, 2000); // Todos los estudiantes anteriores

        PaymentConceptUpdatedRelations::dispatch(
            $conceptId,
            $oldPaymentConceptArray,
            $dtoArray,
            $appliesTo,
            $oldRecipientIds
        );

        // Assert - Verificar que ambos eventos fueron disparados con datos correctos
        Event::assertDispatched(PaymentConceptUpdatedFields::class, function ($event) use ($conceptId) {
            return $event->conceptId === $conceptId &&
                isset($event->changes['amount']) &&
                isset($event->changes['description']);
        });

        Event::assertDispatched(PaymentConceptUpdatedRelations::class, function ($event) use ($conceptId, $appliesTo) {
            return $event->newPaymentConceptId === $conceptId &&
                $event->appliesTo === $appliesTo &&
                isset($event->dtoArray['careers']) &&
                count($event->dtoArray['careers']) === 3;
        });
    }

}
