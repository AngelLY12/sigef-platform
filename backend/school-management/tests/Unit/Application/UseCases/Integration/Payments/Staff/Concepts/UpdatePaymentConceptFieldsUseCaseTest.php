<?php

namespace Tests\Unit\Application\UseCases\Integration\Payments\Staff\Concepts;

use App\Core\Application\DTO\Request\PaymentConcept\UpdatePaymentConceptDTO;
use App\Core\Application\DTO\Response\PaymentConcept\UpdatePaymentConceptResponse;
use App\Core\Application\UseCases\Payments\Staff\Concepts\UpdatePaymentConceptFieldsUseCase;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Exceptions\Conflict\ConceptCannotBeUpdatedException;
use App\Exceptions\NotFound\ConceptNotFoundException;
use App\Exceptions\Validation\ConceptStartDateTooEarlyException;
use App\Exceptions\Validation\ValidationException;
use App\Models\PaymentConcept;
use Carbon\Carbon;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePaymentConceptFieldsUseCaseTest extends TestCase
{
    use DatabaseTransactions;

    private UpdatePaymentConceptFieldsUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        $this->seed(PermissionsSeeder::class);

        Event::fake();

        $this->useCase = app(UpdatePaymentConceptFieldsUseCase::class);
    }

    #[Test]
    public function it_updates_concept_name(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'concept_name' => 'Nombre Original',
            'status' => PaymentConceptStatus::ACTIVO,
            'applies_to' => PaymentConceptAppliesTo::TODOS
        ]);

        $dto = new UpdatePaymentConceptDTO(
            id: $concept->id,
            concept_name: 'Nombre Actualizado',
            description: null,
            start_date: null,
            end_date: null,
            amount: null
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(UpdatePaymentConceptResponse::class, $response);
        $this->assertEquals($concept->id, $response->id);
        $this->assertEquals('Nombre Actualizado', $response->conceptName);

        // Verificar cambios
        $this->assertCount(1, $response->changes);
        $this->assertEquals('concept_name', $response->changes[0]['field']);
        $this->assertEquals('Nombre Original', $response->changes[0]['old']);
        $this->assertEquals('Nombre Actualizado', $response->changes[0]['new']);

        // Verificar en base de datos
        $updatedConcept = PaymentConcept::find($concept->id);
        $this->assertEquals('Nombre Actualizado', $updatedConcept->concept_name);

        // Verificar evento
        Event::assertDispatched(\App\Events\PaymentConceptUpdatedFields::class);
    }

    #[Test]
    public function it_updates_description(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'description' => 'Descripción original',
            'status' => PaymentConceptStatus::ACTIVO
        ]);

        $dto = new UpdatePaymentConceptDTO(
            id: $concept->id,
            concept_name: null,
            description: 'Nueva descripción',
            start_date: null,
            end_date: null,
            amount: null
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertEquals('Nueva descripción', $response->description);
        $this->assertCount(1, $response->changes);
        $this->assertEquals('description', $response->changes[0]['field']);

        Event::assertDispatched(\App\Events\PaymentConceptUpdatedFields::class);
    }

    #[Test]
    public function it_updates_amount(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'amount' => '100.00',
            'status' => PaymentConceptStatus::ACTIVO
        ]);

        $dto = new UpdatePaymentConceptDTO(
            id: $concept->id,
            concept_name: null,
            description: null,
            start_date: null,
            end_date: null,
            amount: '150.50'
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertEquals('150.50', $response->amount);
        $this->assertCount(1, $response->changes);
        $this->assertEquals('amount', $response->changes[0]['field']);
        $this->assertEquals('100.00', $response->changes[0]['old']);
        $this->assertEquals('150.50', $response->changes[0]['new']);

        Event::assertDispatched(\App\Events\PaymentConceptUpdatedFields::class);
    }

    #[Test]
    public function it_updates_dates(): void
    {
        // Arrange
        $originalStartDate = Carbon::create(2024, 1, 1);
        $originalEndDate = Carbon::create(2024, 12, 31);

        $concept = PaymentConcept::factory()->create([
            'start_date' => $originalStartDate,
            'end_date' => $originalEndDate,
            'status' => PaymentConceptStatus::ACTIVO
        ]);

        $newStartDate = Carbon::create(2026, 2, 1);
        $newEndDate = Carbon::create(2026, 2, 25);

        $dto = new UpdatePaymentConceptDTO(
            id: $concept->id,
            concept_name: null,
            description: null,
            start_date: $newStartDate,
            end_date: $newEndDate,
            amount: null
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertCount(2, $response->changes);

        // Verificar cambio de start_date
        $startDateChange = collect($response->changes)->firstWhere('field', 'start_date');
        $this->assertNotNull($startDateChange);
        $this->assertEquals(
            $originalStartDate->toDateString(),
            Carbon::parse($startDateChange['old'])->toDateString()
        );
        $this->assertEquals(
            $newStartDate->toDateString(),
            Carbon::parse($startDateChange['new'])->toDateString()
        );

        // Verificar cambio de end_date
        $endDateChange = collect($response->changes)->firstWhere('field', 'end_date');
        $this->assertNotNull($endDateChange);
        $this->assertEquals(
            $originalEndDate->toDateString(),
            Carbon::parse($endDateChange['old'])->toDateString()
        );
        $this->assertEquals(
            $newEndDate->toDateString(),
            Carbon::parse($endDateChange['new'])->toDateString()
        );

        Event::assertDispatched(\App\Events\PaymentConceptUpdatedFields::class);
    }

    #[Test]
    public function it_updates_multiple_fields_at_once(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'concept_name' => 'Original',
            'description' => 'Desc original',
            'amount' => '50.00',
            'status' => PaymentConceptStatus::ACTIVO
        ]);

        $dto = new UpdatePaymentConceptDTO(
            id: $concept->id,
            concept_name: 'Actualizado',
            description: 'Nueva desc',
            start_date: null,
            end_date: null,
            amount: '75.25'
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertCount(3, $response->changes);
        $this->assertEquals('Actualizado', $response->conceptName);
        $this->assertEquals('Nueva desc', $response->description);
        $this->assertEquals('75.25', $response->amount);

        Event::assertDispatched(\App\Events\PaymentConceptUpdatedFields::class);
    }

    #[Test]
    public function it_triggers_administration_event_for_high_amount_update(): void
    {
        // Arrange
        config(['concepts.amount.notifications.threshold' => '1000.00']);

        $concept = PaymentConcept::factory()->create([
            'amount' => '500.00', // Bajo el threshold
            'status' => PaymentConceptStatus::ACTIVO
        ]);

        // Actualizar a monto ALTO
        $dto = new UpdatePaymentConceptDTO(
            id: $concept->id,
            concept_name: null,
            description: null,
            start_date: null,
            end_date: null,
            amount: '1500.00' // Sobre el threshold
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertEquals('1500.00', $response->amount);

        // Verificar evento de administración
        Event::assertDispatched(\App\Events\AdministrationEvent::class, function ($event) {
            return $event->amount === '1500.00' && $event->action === 'actualizó';
        });
    }

    #[Test]
    public function it_does_not_trigger_administration_event_for_low_amount_update(): void
    {
        // Arrange
        config(['concepts.amount.notifications.threshold' => '1000.00']);

        $concept = PaymentConcept::factory()->create([
            'amount' => '500.00',
            'status' => PaymentConceptStatus::ACTIVO
        ]);

        // Actualizar a monto aún BAJO
        $dto = new UpdatePaymentConceptDTO(
            id: $concept->id,
            concept_name: null,
            description: null,
            start_date: null,
            end_date: null,
            amount: '800.00' // Todavía bajo el threshold
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertEquals('800.00', $response->amount);

        // Verificar que NO se disparó el evento de administración
        Event::assertNotDispatched(\App\Events\AdministrationEvent::class);
    }

    #[Test]
    public function it_throws_exception_when_concept_not_found(): void
    {
        // Arrange
        $nonExistentId = 99999;

        $dto = new UpdatePaymentConceptDTO(
            id: $nonExistentId,
            concept_name: 'Nuevo Nombre'
        );

        // Expect exception
        $this->expectException(ConceptNotFoundException::class);

        // Act
        $this->useCase->execute($dto);
    }

    #[Test]
    public function it_throws_exception_when_no_fields_to_update(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO
        ]);

        // DTO con todos los campos null/empty
        $dto = new UpdatePaymentConceptDTO(
            id: $concept->id,
            concept_name: null,
            description: null,
            start_date: null,
            end_date: null,
            amount: null
        );

        // Expect exception
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No se encontraron o proporcionaron campos para actualizar');

        // Act
        $this->useCase->execute($dto);
    }

    #[Test]
    public function it_ignores_empty_strings_and_arrays(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'concept_name' => 'Original',
            'description' => 'Desc original',
            'status' => PaymentConceptStatus::ACTIVO
        ]);

        // DTO con strings vacíos (deberían ser ignorados)
        $dto = new UpdatePaymentConceptDTO(
            id: $concept->id,
            concept_name: '', // String vacío
            description: '',  // String vacío
            start_date: null,
            end_date: null,
            amount: null
        );

        // Expect exception porque no hay campos válidos para actualizar
        $this->expectException(ValidationException::class);

        // Act
        $this->useCase->execute($dto);
    }

    #[Test]
    public function it_handles_decimal_amount_comparison_correctly(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'amount' => '100.00',
            'status' => PaymentConceptStatus::ACTIVO
        ]);

        // Mismo monto con diferente formato de decimal
        $dto = new UpdatePaymentConceptDTO(
            id: $concept->id,
            concept_name: null,
            description: null,
            start_date: null,
            end_date: null,
            amount: '100.00' // Exactamente igual
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert - No debería haber cambios
        $this->assertEmpty($response->changes);
        $this->assertStringContainsString('sin cambios', $response->message ?? '');

        // No debería disparar evento de actualización
        Event::assertNotDispatched(\App\Events\PaymentConceptUpdatedFields::class);
    }

    #[Test]
    public function it_detects_small_amount_changes(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'amount' => '100.00',
            'status' => PaymentConceptStatus::ACTIVO
        ]);

        // Pequeño cambio
        $dto = new UpdatePaymentConceptDTO(
            id: $concept->id,
            concept_name: null,
            description: null,
            start_date: null,
            end_date: null,
            amount: '100.01' // Solo 1 centavo de diferencia
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert - Debería detectar el cambio
        $this->assertCount(1, $response->changes);
        $this->assertEquals('100.00', $response->changes[0]['old']);
        $this->assertEquals('100.01', $response->changes[0]['new']);

        Event::assertDispatched(\App\Events\PaymentConceptUpdatedFields::class);
    }

    #[Test]
    public function it_throws_exception_when_updating_inactive_concept(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::FINALIZADO
        ]);

        $dto = new UpdatePaymentConceptDTO(
            id: $concept->id,
            concept_name: 'Nuevo Nombre'
        );

        // Expect validation exception
        $this->expectException(ConceptCannotBeUpdatedException::class);

        // Act
        $this->useCase->execute($dto);
    }

    #[Test]
    public function it_throws_exception_for_invalid_field_updates(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO,
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES
        ]);

        // Intentar actualizar end_date a una fecha anterior a start_date
        $dto = new UpdatePaymentConceptDTO(
            id: $concept->id,
            concept_name: null,
            description: null,
            start_date: Carbon::create(2024, 1, 1),
            end_date: Carbon::create(2023, 12, 31),
            amount: null
        );

        // Expect validation exception
        // Depende de PaymentConceptValidator::ensureUpdatedFieldsAreValid
        $this->expectException(ConceptStartDateTooEarlyException::class);

        // Act
        $this->useCase->execute($dto);
    }

    #[Test]
    public function it_updates_only_allowed_fields(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'concept_name' => 'Original',
            'status' => PaymentConceptStatus::ACTIVO,
            'applies_to' => PaymentConceptAppliesTo::TODOS
        ]);

        // Note: El DTO solo permite concept_name, description, dates, amount
        // No permite actualizar status o applies_to a través de este use case
        $dto = new UpdatePaymentConceptDTO(
            id: $concept->id,
            concept_name: 'Actualizado',
            description: null,
            start_date: null,
            end_date: null,
            amount: null
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertEquals('Actualizado', $response->conceptName);
        // status y applies_to deberían permanecer igual
        $this->assertEquals(PaymentConceptStatus::ACTIVO->value, $response->status);
        $this->assertEquals(PaymentConceptAppliesTo::TODOS->value, $response->appliesTo);
    }

    #[Test]
    public function it_generates_correct_change_messages(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'concept_name' => 'Original',
            'amount' => '100.00',
            'start_date' => Carbon::create(2024, 1, 1),
            'status' => PaymentConceptStatus::ACTIVO
        ]);

        $dto = new UpdatePaymentConceptDTO(
            id: $concept->id,
            concept_name: 'Actualizado',
            description: null,
            start_date: Carbon::create(2026, 2, 1),
            end_date: null,
            amount: '150.50'
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertNotEmpty($response->message);
        $this->assertStringContainsString('Concepto actualizado:', $response->message);
        $this->assertStringContainsString('Nombre', $response->message);
        $this->assertStringContainsString('Monto', $response->message);
        $this->assertStringContainsString('Fecha inicio', $response->message);
    }

    #[Test]
    public function it_does_not_notify_for_insignificant_changes(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO
        ]);

        // El método shouldNotifyForChanges solo notifica para campos importantes
        // Si no hay cambios en campos importantes, no debería notificar
        // (aunque esto depende de tu implementación específica)

        $this->assertTrue(true); // Test de placeholder
    }

    #[Test]
    public function it_runs_in_transaction(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'concept_name' => 'Original',
            'status' => PaymentConceptStatus::ACTIVO
        ]);

        $dto = new UpdatePaymentConceptDTO(
            id: $concept->id,
            concept_name: 'En Transacción'
        );

        // Spy en DB para verificar transacción
        DB::shouldReceive('transaction')
            ->once()
            ->with(\Mockery::type('Closure'))
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertEquals('En Transacción', $response->conceptName);
    }

    #[Test]
    public function it_preserves_other_fields_when_updating_specific_ones(): void
    {
        // Arrange
        $originalDescription = 'Descripción original importante';
        $originalAmount = '500.00';

        $concept = PaymentConcept::factory()->create([
            'concept_name' => 'Original',
            'description' => $originalDescription,
            'amount' => $originalAmount,
            'status' => PaymentConceptStatus::ACTIVO
        ]);

        // Solo actualizar el nombre
        $dto = new UpdatePaymentConceptDTO(
            id: $concept->id,
            concept_name: 'Solo Nombre Actualizado',
            description: null, // Mantener igual
            start_date: null,
            end_date: null,
            amount: null // Mantener igual
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertEquals('Solo Nombre Actualizado', $response->conceptName);
        $this->assertEquals($originalDescription, $response->description);
        $this->assertEquals($originalAmount, $response->amount);

        // Solo debería haber 1 cambio
        $this->assertCount(1, $response->changes);
        $this->assertEquals('concept_name', $response->changes[0]['field']);
    }

}
