<?php

namespace Tests\Unit\Application\UseCases\Integration\Payments\Staff\Concepts;

use App\Core\Application\UseCases\Payments\Staff\Concepts\ActivatePaymentConceptUseCase;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Infraestructure\Mappers\PaymentConceptMapper;
use App\Exceptions\Conflict\ConceptAlreadyActiveException;
use App\Jobs\ClearCacheForUsersJob;
use App\Models\PaymentConcept;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ActivatePaymentConceptUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private ActivatePaymentConceptUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed necessary data
        $this->seed(RolesSeeder::class);
        $this->seed(PermissionsSeeder::class);

        // Mock events and queues to prevent side effects
        Event::fake();
        Bus::fake();
        $this->useCase = app(ActivatePaymentConceptUseCase::class);
    }

    #[Test]
    public function it_activates_a_payment_concept_successfully(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->inactive()->create();

        // Act
        $response = $this->useCase->execute(PaymentConceptMapper::toDomain($concept));

        // Assert
        $this->assertEquals('El concepto fue activado correctamente', $response->message);
        $this->assertEquals(PaymentConceptStatus::ACTIVO->value, $response->conceptData['status']);

        // Verify changes array
        $this->assertCount(1, $response->changes);
        $this->assertEquals('status', $response->changes[0]['field']);
        $this->assertEquals(PaymentConceptStatus::DESACTIVADO->value, $response->changes[0]['old']);
        $this->assertEquals(PaymentConceptStatus::ACTIVO->value, $response->changes[0]['new']);
        $this->assertEquals('activate', $response->changes[0]['transition_type']);

        // Verify the concept was actually updated in database
        $updatedConcept = PaymentConcept::find($concept->id);
        $this->assertEquals(PaymentConceptStatus::ACTIVO, $updatedConcept->status);

        // Verify event was dispatched
        Event::assertDispatched(\App\Events\PaymentConceptStatusChanged::class);
    }

    #[Test]
    public function it_throws_exception_when_activating_already_active_concept(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->active()->create();

        // Expect exception
        $this->expectException(ConceptAlreadyActiveException::class);

        // Act
        $this->useCase->execute(PaymentConceptMapper::toDomain($concept));
    }

    #[Test]
    public function it_dispatches_cache_clear_jobs_for_affected_users(): void
    {

        // Create users with STUDENT role (assuming concept applies to students)
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        $users = User::factory()->count(3)->create([
            'status' => UserStatus::ACTIVO
        ]);

        foreach ($users as $user) {
            $user->assignRole($studentRole);
        }

        $concept = PaymentConcept::factory()->inactive()->create([
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES
        ]);

        $concept->users()->sync($users->pluck('id')->toArray());

        // Act
        $response = $this->useCase->execute(PaymentConceptMapper::toDomain($concept));

        // Assert
        $this->assertEquals('El concepto fue activado correctamente', $response->message);

        // Verify cache clear jobs were dispatched with proper parameters
        Bus::assertDispatched(ClearCacheForUsersJob::class);
    }

    #[Test]
    public function it_does_not_dispatch_cache_jobs_when_no_users_affected(): void
    {
        // Arrange
        Queue::fake();

        // Create a concept that applies to a role with no users
        $concept = PaymentConcept::factory()->inactive()->create();

        // Act
        $response = $this->useCase->execute(PaymentConceptMapper::toDomain($concept));

        // Assert
        $this->assertEquals('El concepto fue activado correctamente', $response->message);

        // Verify no cache jobs were dispatched
        Bus::assertNotDispatched(\App\Jobs\ClearCacheForUsersJob::class);
    }

    #[Test]
    public function it_chunks_users_for_cache_clear_jobs(): void
    {

        // Create more users than CHUNK_SIZE
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $totalUsers = 1200; // More than CHUNK_SIZE (500)

        $users = User::factory()->count($totalUsers)->create([
            'status' => UserStatus::ACTIVO
        ]);

        foreach ($users as $user) {
            $user->assignRole($studentRole);
        }

        $concept = PaymentConcept::factory()->inactive()->create([
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES
        ]);
        $concept->users()->sync($users->pluck('id')->toArray());
        // Act
        $response = $this->useCase->execute(PaymentConceptMapper::toDomain($concept));

        // Assert
        $this->assertEquals('El concepto fue activado correctamente', $response->message);

        // Calculate expected number of chunks
        $expectedChunks = (int) ceil($totalUsers / 500); // CHUNK_SIZE = 500

        // Verify jobs were chunked properly
        Bus::assertDispatchedTimes(ClearCacheForUsersJob::class, $expectedChunks);
    }

    #[Test]
    public function it_handles_concept_with_all_users_target(): void
    {
        // Arrange
        Queue::fake();

        // Create users with different roles
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        $students = User::factory()->count(10)->create(['status' => UserStatus::ACTIVO]);

        foreach ($students as $student) {
            $student->assignRole($studentRole);
        }

        $concept = PaymentConcept::factory()->inactive()->create([
            'applies_to' => PaymentConceptAppliesTo::TODOS
        ]);


        // Act
        $response = $this->useCase->execute(PaymentConceptMapper::toDomain($concept));

        // Assert
        $this->assertEquals('El concepto fue activado correctamente', $response->message);

        // Verify cache jobs were dispatched for all users
        Bus::assertDispatched(\App\Jobs\ClearCacheForUsersJob::class);
    }

    #[Test]
    public function it_preserves_other_concept_attributes_when_activating(): void
    {
        // Arrange
        $originalAmount = '150.75';
        $originalName = 'Test Preservation Concept';

        $concept = PaymentConcept::factory()->inactive()->create([
           'concept_name' => $originalName,
           'amount' => $originalAmount,
           'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES
        ]);

        // Act
        $response = $this->useCase->execute(PaymentConceptMapper::toDomain($concept));

        // Assert
        $this->assertEquals('El concepto fue activado correctamente', $response->message);

        // Fetch updated concept from database
        $updatedConcept = PaymentConcept::find($concept->id);

        // Verify status changed
        $this->assertEquals(PaymentConceptStatus::ACTIVO, $updatedConcept->status);

        // Verify other attributes remain unchanged
        $this->assertEquals($originalAmount, $updatedConcept->amount);
        $this->assertEquals($originalName, $updatedConcept->concept_name);
        $this->assertEquals(PaymentConceptAppliesTo::ESTUDIANTES, $updatedConcept->applies_to);
    }

}
