<?php

namespace Tests\Unit\Application\UseCases\Integration\Payments\Students\Dashboard;

use App\Core\Application\DTO\Response\PaymentConcept\PendingSummaryResponse;
use App\Core\Application\UseCases\Payments\Student\Dashboard\OverduePaymentsUseCase;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\User\UserRoles;
use App\Models\Payment;
use App\Models\PaymentConcept;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use App\Models\User as UserModel;

class OverduePaymentsUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private OverduePaymentsUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->useCase = app(OverduePaymentsUseCase::class);
        $this->seed(RolesSeeder::class);
    }

    private function createUserEntity(UserModel $user): \App\Core\Domain\Entities\User
    {
        $user->load('roles');
        return \App\Core\Infraestructure\Mappers\UserMapper::toDomain($user);
    }

    #[Test]
    public function it_returns_zero_when_no_overdue_payment_concepts(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        $userEntity = $this->createUserEntity($user);

        // Act
        $result = $this->useCase->execute($userEntity, false);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        $this->assertEquals('0.00', $result->totalAmount);
        $this->assertEquals(0, $result->totalCount);
    }

    #[Test]
    public function it_returns_correct_summary_with_overdue_concepts(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        // Crear conceptos de pago finalizados que apliquen al usuario
        PaymentConcept::factory()->count(3)->create([
            'status' => PaymentConceptStatus::FINALIZADO,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'amount' => '1000.00',
            'start_date' => now()->subMonths(3),
            'end_date' => now()->subMonth(), // Finalizado (vencido)
        ]);

        $userEntity = $this->createUserEntity($user);

        // Act
        $result = $this->useCase->execute($userEntity, false);

        // Assert - Debería sumar los 3 conceptos (3000 total)
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        $this->assertEquals('3000.00', $result->totalAmount);
        $this->assertEquals(3, $result->totalCount);
    }

    #[Test]
    public function it_subtracts_payments_made_from_concepts(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        // Crear concepto de pago finalizado
        $concept = PaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::FINALIZADO,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'amount' => '1000.00',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subMonth(),
        ]);

        // Crear pago PARCIAL para ese concepto
        Payment::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept->id,
            'amount' => '1000.00',
            'amount_received' => '600.00', // Solo pagó 600
            'status' => PaymentStatus::UNDERPAID,
        ]);

        $userEntity = $this->createUserEntity($user);

        // Act
        $result = $this->useCase->execute($userEntity, false);

        // Assert - Debería mostrar 400 adeudados (1000 - 600)
        $this->assertEquals('400.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);
    }

    #[Test]
    public function it_does_not_count_concepts_with_full_payment(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        // Crear concepto de pago finalizado
        $concept = PaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::FINALIZADO,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'amount' => '1000.00',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subMonth(),
        ]);

        // Crear pago COMPLETO para ese concepto
        Payment::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept->id,
            'amount' => '1000.00',
            'amount_received' => '1000.00', // Pagó completo
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        $userEntity = $this->createUserEntity($user);

        // Act
        $result = $this->useCase->execute($userEntity, false);

        // Assert - No debería contar porque ya está pagado completo
        $this->assertEquals('0.00', $result->totalAmount);
        $this->assertEquals(0, $result->totalCount);
    }

    #[Test]
    public function it_filters_by_current_year_when_onlyThisYear_is_true(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        $currentYear = date('Y');
        $lastYear = $currentYear - 1;

        // Concepto este año
        PaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::FINALIZADO,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'amount' => '800.00',
            'start_date' => "{$currentYear}-01-15",
            'end_date' => "{$currentYear}-06-15",
            'created_at' => "{$currentYear}-01-01",
        ]);

        // Concepto año pasado
        PaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::FINALIZADO,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'amount' => '1200.00',
            'start_date' => "{$lastYear}-01-15",
            'end_date' => "{$lastYear}-06-15",
            'created_at' => "{$lastYear}-01-01",
        ]);

        $userEntity = $this->createUserEntity($user);

        // Act - Solo este año
        $resultThisYear = $this->useCase->execute($userEntity, true);

        // Act - Todos los años
        $resultAllYears = $this->useCase->execute($userEntity, false);

        // Assert
        $this->assertEquals('800.00', $resultThisYear->totalAmount); // Solo el de este año
        $this->assertEquals(1, $resultThisYear->totalCount);

        $this->assertEquals('2000.00', $resultAllYears->totalAmount); // Ambos
        $this->assertEquals(2, $resultAllYears->totalCount);
    }

    #[Test]
    public function it_only_counts_finalized_concepts(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        // 2 conceptos finalizados (deben contar)
        PaymentConcept::factory()->count(2)->create([
            'status' => PaymentConceptStatus::FINALIZADO,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'amount' => '500.00',
            'start_date' => now()->subMonths(3),
            'end_date' => now()->subMonth(),
        ]);

        // 1 concepto activo (NO debe contar aunque tenga fecha pasada)
        PaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::ACTIVO, // Activo, no finalizado
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'amount' => '1000.00',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subDays(10),
        ]);

        $userEntity = $this->createUserEntity($user);

        // Act
        $result = $this->useCase->execute($userEntity, false);

        // Assert - Solo los 2 finalizados
        $this->assertEquals('1000.00', $result->totalAmount); // 2 * 500
        $this->assertEquals(2, $result->totalCount);
    }

    #[Test]
    public function it_applies_correct_filters_based_on_user_attributes(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $career = \App\Models\Career::factory()->create();
        // Asignar career_id y semestre al usuario (simulando studentDetail)
        $user->studentDetail()->create([
            'career_id' => $career->id,
            'semestre' => 5,
        ]);
        $user->load('studentDetail');

        // Concepto que aplica a TODOS (debe contar)
        PaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::FINALIZADO,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'amount' => '1000.00',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subMonth(),
        ]);

        // Concepto que aplica a carrera específica (debe contar si match)
        $conceptCarrera=PaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::FINALIZADO,
            'applies_to' => PaymentConceptAppliesTo::CARRERA->value,
            'amount' => '800.00',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subMonth(),
        ]);
        $conceptCarrera->careers()->attach($career->id);

        $userEntity = $this->createUserEntity($user);

        // Act
        $result = $this->useCase->execute($userEntity, false);

        $this->assertEquals('1800.00', $result->totalAmount);
        $this->assertEquals(2, $result->totalCount);
    }

    #[Test]
    public function it_handles_concepts_for_specific_students(): void
    {
        // Arrange - Dos usuarios
        $user1 = UserModel::factory()->asStudent()->create(['email' => 'user1@test.com']);
        $user2 = UserModel::factory()->asStudent()->create(['email' => 'user2@test.com']);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user1->assignRole($studentRole);
        $user2->assignRole($studentRole);

        // Concepto que aplica solo a user1
        $concept = PaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::FINALIZADO,
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES->value,
            'amount' => '1500.00',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subMonth(),
        ]);

        // Asociar concepto solo a user1
        $concept->users()->attach($user1->id);

        $userEntity1 = $this->createUserEntity($user1);
        $userEntity2 = $this->createUserEntity($user2);

        // Act
        $result1 = $this->useCase->execute($userEntity1, false);
        $result2 = $this->useCase->execute($userEntity2, false);

        // Assert - Solo user1 debería tener el concepto
        $this->assertEquals('1500.00', $result1->totalAmount);
        $this->assertEquals(1, $result1->totalCount);

        $this->assertEquals('0.00', $result2->totalAmount);
        $this->assertEquals(0, $result2->totalCount);
    }

    #[Test]
    public function it_excludes_concepts_with_exceptions(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        // Concepto finalizado
        $concept = PaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::FINALIZADO,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'amount' => '2000.00',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subMonth(),
        ]);

        // Crear excepción para este usuario (no debe contar)
        $concept->exceptions()->sync($user->id);

        $userEntity = $this->createUserEntity($user);

        // Act
        $result = $this->useCase->execute($userEntity, false);

        // Assert - No debería contar por la excepción
        $this->assertEquals('0.00', $result->totalAmount);
        $this->assertEquals(0, $result->totalCount);
    }

    #[Test]
    public function it_calculates_complex_scenario_correctly(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        // Escenario complejo:
        // 1. Concepto 1000 - Pagado 600 = Adeuda 400
        $concept1 = PaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::FINALIZADO,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'amount' => '1000.00',
            'start_date' => now()->subMonths(3),
            'end_date' => now()->subMonth(),
        ]);
        Payment::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept1->id,
            'amount' => '1000.00',
            'amount_received' =>'600.00',
            'status' => PaymentStatus::UNDERPAID,
        ]);

        // 2. Concepto 800 - Pagado 800 = Adeuda 0 (no cuenta)
        $concept2 = PaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::FINALIZADO,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'amount' => '800.00',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subMonth(),
        ]);
        Payment::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept2->id,
            'amount' => '800.00',
            'amount_received' => '800.00',
            'status' => PaymentStatus::SUCCEEDED,
        ]);

        // 3. Concepto 1200 - Sin pago = Adeuda 1200
        PaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::FINALIZADO,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'amount' => '1200.00',
            'start_date' => now()->subMonths(2),
            'end_date' => now()->subMonth(),
        ]);

        $userEntity = $this->createUserEntity($user);

        // Act
        $result = $this->useCase->execute($userEntity, false);

        // Assert - Total: 400 + 1200 = 1600, Count: 2 conceptos con adeudo
        $this->assertEquals('1600.00', $result->totalAmount);
        $this->assertEquals(2, $result->totalCount);
    }

    #[Test]
    public function it_correctly_calculates_single_payment_per_concept(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        $concept = PaymentConcept::factory()->create([
            'status' => PaymentConceptStatus::FINALIZADO,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'amount' => '2000.00',
            'start_date' => now()->subMonths(3),
            'end_date' => now()->subMonth(),
        ]);

        // UN solo pago para el concepto (como en tu sistema real)
        Payment::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept->id,
            'amount' => '2000.00',
            'amount_received' => '800.00', // Pagó 800 de 2000
            'status' => PaymentStatus::UNDERPAID, // Bajo pagado
        ]);

        $userEntity = $this->createUserEntity($user);

        // Act
        $result = $this->useCase->execute($userEntity, false);

        // Assert - 2000 - 800 = 1200 adeudados
        $this->assertEquals('1200.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);
    }

    #[Test]
    public function it_returns_valid_response_structure(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);

        $userEntity = $this->createUserEntity($user);

        // Act
        $result = $this->useCase->execute($userEntity, false);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        $this->assertObjectHasProperty('totalAmount', $result);
        $this->assertObjectHasProperty('totalCount', $result);

        // Tipos correctos
        $this->assertIsNumeric($result->totalAmount);
        $this->assertIsInt($result->totalCount);
    }
}
