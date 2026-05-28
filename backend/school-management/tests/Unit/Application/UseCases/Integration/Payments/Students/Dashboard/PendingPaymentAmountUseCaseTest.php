<?php

namespace Tests\Unit\Application\UseCases\Integration\Payments\Students\Dashboard;

use App\Core\Application\DTO\Response\PaymentConcept\PendingSummaryResponse;
use App\Core\Application\UseCases\Payments\Student\Dashboard\PendingPaymentAmountUseCase;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Utils\Helpers\Money;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Models\Career;
use App\Models\StudentDetail;
use Carbon\Carbon;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use App\Models\User as UserModel;
use App\Models\Payment as PaymentModel;
use App\Models\PaymentConcept as PaymentConceptModel;

class PendingPaymentAmountUseCaseTest extends TestCase
{
    use RefreshDatabase;

    private PendingPaymentAmountUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->useCase = app(PendingPaymentAmountUseCase::class);
        $this->seed(RolesSeeder::class);
    }

    #[Test]
    public function it_returns_zero_pending_summary_when_no_payment_concepts_exist(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($userEntity, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        $this->assertEquals('0.00', $result->totalAmount);
        $this->assertEquals(0, $result->totalCount);
    }

    #[Test]
    public function it_calculates_pending_amount_for_active_payment_concepts(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Crear conceptos de pago activos que aplican a TODOS
        $concept1 = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        $concept2 = PaymentConceptModel::factory()->create([
            'amount' => '750.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->addDays(25),
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($userEntity, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        // Total: 500 + 750 = 1250.00
        $this->assertEquals('1250.00', $result->totalAmount);
        $this->assertEquals(2, $result->totalCount);
    }

    #[Test]
    public function it_excludes_payment_concepts_with_completed_payments(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto con pago completado
        $conceptWithPayment = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $conceptWithPayment->id,
            'amount' => '500.00',
            'amount_received' => '500.00',
            'status' => PaymentStatus::SUCCEEDED->value,
        ]);

        // Concepto sin pago
        $conceptWithoutPayment = PaymentConceptModel::factory()->create([
            'amount' => '750.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->addDays(25),
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($userEntity, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        // Solo debe incluir el concepto sin pago: 750.00
        $this->assertEquals('750.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);
    }

    #[Test]
    public function it_includes_partial_payments_in_pending_calculation(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $concept = PaymentConceptModel::factory()->create([
            'amount' => '1000.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Pago parcial con estado no completado
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept->id,
            'amount' => '1000.00',
            'amount_received' => '400.00',
            'status' => PaymentStatus::UNDERPAID->value, // Estado no terminal
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($userEntity, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        // Pendiente: 1000 - 400 = 600.00
        $this->assertEquals('600.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);
    }

    #[Test]
    public function it_filters_by_current_year_when_onlyThisYear_is_true(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $currentYear = now()->year;

        // Concepto del año actual
        PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::create($currentYear, 1, 15),
            'end_date' => Carbon::create($currentYear, 12, 31),
            'created_at' => Carbon::create($currentYear, 1, 15),
        ]);

        // Concepto del año anterior (no debe incluirse)
        PaymentConceptModel::factory()->create([
            'amount' => '750.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::create($currentYear - 1, 1, 15),
            'end_date' => Carbon::create($currentYear - 1, 12, 31),
            'created_at' => Carbon::create($currentYear - 1, 1, 15),
        ]);

        $onlyThisYear = true;

        // Act
        $result = $this->useCase->execute($userEntity, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        // Solo concepto del año actual: 500.00
        $this->assertEquals('500.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);
    }

    #[Test]
    public function it_includes_all_years_when_onlyThisYear_is_false(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $currentYear = now()->year;

        // Concepto del año actual
        PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::create($currentYear, 1, 15),
            'end_date' => Carbon::create($currentYear, 12, 31),
            'created_at' => Carbon::create($currentYear, 1, 15),
        ]);

        // Concepto del año anterior - CORRECCIÓN: Asegurar que sea del año anterior
        PaymentConceptModel::factory()->create([
            'amount' => '750.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::create($currentYear - 1, 1, 15),
            'end_date' => Carbon::create($currentYear - 1, 12, 31),
            'created_at' => Carbon::create($currentYear - 1, 1, 15),
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($userEntity, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        $this->assertEquals('500.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);
    }

    #[Test]
    public function it_excludes_inactive_payment_concepts(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto activo
        PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Concepto inactivo - CORRECCIÓN: Usar el estado correcto
        PaymentConceptModel::factory()->create([
            'amount' => '750.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::DESACTIVADO->value, // Estado correcto
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($userEntity, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        // Solo concepto activo: 500.00
        $this->assertEquals('500.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);
    }

    #[Test]
    public function it_excludes_payment_concepts_with_future_start_date(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto con fecha de inicio futura
        PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->addDays(10),
            'end_date' => Carbon::now()->addDays(30),
        ]);

        // Concepto vigente
        PaymentConceptModel::factory()->create([
            'amount' => '750.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($userEntity, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        // Solo concepto vigente: 750.00
        $this->assertEquals('750.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);
    }

    #[Test]
    public function it_excludes_payment_concepts_with_past_end_date(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto con fecha de fin pasada
        PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(5),
        ]);

        // Concepto vigente
        PaymentConceptModel::factory()->create([
            'amount' => '750.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => null, // Sin fecha de fin
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($userEntity, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        // Solo concepto vigente: 750.00
        $this->assertEquals('750.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);
    }

    #[Test]
    public function it_filters_by_career_when_user_has_career(): void
    {
        // Arrange
        // CORRECCIÓN: Usar el factory correcto de Career
        $career = Career::factory()->create([
            'career_name' => 'Ingeniería de Sistemas' // Usar el nombre de columna correcto
        ]);

        // Crear student detail por separado
        $user = UserModel::factory()->asStudent()->create();
        StudentDetail::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id,
            'semestre' => 5,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load(['roles', 'studentDetail']);
        $userEntity = UserMapper::toDomain($user);

        // Concepto para TODOS (debe incluirse)
        PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Concepto para carrera específica (debe incluirse)
        $careerConcept = PaymentConceptModel::factory()->create([
            'amount' => '750.00',
            'applies_to' => PaymentConceptAppliesTo::CARRERA->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        $careerConcept->careers()->attach($career->id);

        // Concepto para otra carrera (NO debe incluirse)
        $otherCareer = Career::factory()->create([
            'career_name' => 'Medicina'
        ]);

        $otherCareerConcept = PaymentConceptModel::factory()->create([
            'amount' => '1000.00',
            'applies_to' => PaymentConceptAppliesTo::CARRERA->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        $otherCareerConcept->careers()->attach($otherCareer->id);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($userEntity, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        // Solo conceptos para TODOS y para la carrera del usuario: 500 + 750 = 1250.00
        $this->assertEquals('1250.00', $result->totalAmount);
        $this->assertEquals(2, $result->totalCount);
    }

    #[Test]
    public function it_filters_by_semester_when_user_has_semester(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();

        // Crear student detail con semestre pero sin carrera
        StudentDetail::factory()->create([
            'user_id' => $user->id,
            'career_id' => null,
            'semestre' => 3,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load(['roles', 'studentDetail']);
        $userEntity = UserMapper::toDomain($user);

        // Concepto para TODOS (debe incluirse)
        PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Concepto para semestre 3 (debe incluirse)
        $semesterConcept = PaymentConceptModel::factory()->create([
            'amount' => '750.00',
            'applies_to' => PaymentConceptAppliesTo::SEMESTRE->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // CORRECCIÓN: Usar create en lugar de upsert
        $semesterConcept->paymentConceptSemesters()->create(['semestre' => 3]);

        // Concepto para semestre 5 (NO debe incluirse)
        $otherSemesterConcept = PaymentConceptModel::factory()->create([
            'amount' => '1000.00',
            'applies_to' => PaymentConceptAppliesTo::SEMESTRE->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        $otherSemesterConcept->paymentConceptSemesters()->create(['semestre' => 5]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($userEntity, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        // Solo conceptos para TODOS y para semestre 3: 500 + 750 = 1250.00
        $this->assertEquals('1250.00', $result->totalAmount);
        $this->assertEquals(2, $result->totalCount);
    }

    #[Test]
    public function it_excludes_payment_concepts_with_user_exceptions(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto normal (debe incluirse)
        $normalConcept = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Concepto con excepción para este usuario (NO debe incluirse)
        $exceptedConcept = PaymentConceptModel::factory()->create([
            'amount' => '750.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // CORRECCIÓN: Usar create en lugar de upsert
        $exceptedConcept->exceptions()->sync($user->id);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($userEntity, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        // Solo concepto sin excepción: 500.00
        $this->assertEquals('500.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);
    }

    #[Test]
    public function it_handles_multiple_partial_payments_correctly(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $concept = PaymentConceptModel::factory()->create([
            'amount' => '1500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Primer pago parcial
        $partial=PaymentModel::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept->id,
            'amount' => '1500.00',
            'amount_received' => '500.00',
            'status' => PaymentStatus::UNDERPAID->value,
            'created_at' => Carbon::now()->subDays(5),
        ]);
        $amountReceived = Money::from('500')->add($partial->amount_received)->finalize();
        $partial->update(['amount_received' => $amountReceived]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($userEntity, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        $this->assertEquals('500.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);
    }

    #[Test]
    public function it_excludes_payments_with_terminal_statuses(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // CORRECCIÓN: Crear múltiples conceptos, uno para cada estado
        $amounts = ['1000.00', '1000.00', '1000.00', '1000.00', '1000.00'];
        $totalExpected = 0;

        // Estados que están en paidStatuses() (SUCCEEDED y OVERPAID)
        $paidStatuses = [PaymentStatus::SUCCEEDED->value, PaymentStatus::OVERPAID->value];

        // Crear un concepto para cada estado terminal
        foreach ($paidStatuses as $index => $status) {
            $concept = PaymentConceptModel::factory()->create([
                'amount' => $amounts[$index],
                'applies_to' => PaymentConceptAppliesTo::TODOS->value,
                'status' => PaymentConceptStatus::ACTIVO->value,
                'start_date' => Carbon::now()->subDays(10),
                'end_date' => Carbon::now()->addDays(20),
            ]);

            PaymentModel::factory()->create([
                'user_id' => $user->id,
                'payment_concept_id' => $concept->id,
                'amount' => $amounts[$index],
                'amount_received' => $amounts[$index], // Pago completo
                'status' => $status,
            ]);
        }

        // Crear conceptos con estados NO terminales (estos SÍ deben aparecer)
        for ($i = 2; $i < 5; $i++) {
            $concept = PaymentConceptModel::factory()->create([
                'amount' => $amounts[$i],
                'applies_to' => PaymentConceptAppliesTo::TODOS->value,
                'status' => PaymentConceptStatus::ACTIVO->value,
                'start_date' => Carbon::now()->subDays(10),
                'end_date' => Carbon::now()->addDays(20),
            ]);

            $totalExpected += 1000.00;
        }

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($userEntity, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        // Solo los conceptos con estados NO terminales: 1000 + 1000 + 1000 = 3000.00
        $this->assertEquals($totalExpected, $result->totalAmount);
        $this->assertEquals(3, $result->totalCount);
    }

    #[Test]
    public function it_returns_correct_response_structure(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($userEntity, $onlyThisYear);

        // Assert - Estructura EXACTA del DTO
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        $this->assertObjectHasProperty('totalAmount', $result);
        $this->assertObjectHasProperty('totalCount', $result);

        // Verificar tipos de datos
        $this->assertIsString($result->totalAmount);
        $this->assertIsInt($result->totalCount);

        // Formato de totalAmount debe ser decimal con 2 decimales
        $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $result->totalAmount);
    }

    #[Test]
    public function it_handles_user_without_student_details(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        // No crear StudentDetail para este usuario

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto para TODOS (debe incluirse)
        PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Concepto para carrera (NO debe incluirse porque user no tiene career_id)
        $careerConcept = PaymentConceptModel::factory()->create([
            'amount' => '750.00',
            'applies_to' => PaymentConceptAppliesTo::CARRERA->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        $career = Career::factory()->create(['career_name' => 'Ingeniería']);
        $careerConcept->careers()->attach($career->id);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($userEntity, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        // Solo concepto para TODOS: 500.00
        $this->assertEquals('500.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);
    }

    #[Test]
    public function it_handles_applicant_users(): void
    {
        // Arrange
        // Crear usuario aplicante
        $user = UserModel::factory()->create();

        $applicantRole = Role::where('name', UserRoles::APPLICANT->value)->firstOrFail();
        $user->assignRole($applicantRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto para aplicantes (debe incluirse)
        $applicantConcept = PaymentConceptModel::factory()->create([
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TAG->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // CORRECCIÓN: Usar create en lugar de upsert
        $applicantConcept->applicantTypes()->create([
            'tag' => PaymentConceptApplicantType::APPLICANT->value
        ]);

        // Concepto para estudiantes (NO debe incluirse porque user no es estudiante)
        PaymentConceptModel::factory()->create([
            'amount' => '750.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        $onlyThisYear = false;

        // Act
        $result = $this->useCase->execute($userEntity, $onlyThisYear);

        // Assert
        $this->assertInstanceOf(PendingSummaryResponse::class, $result);
        // Solo concepto para aplicantes: 500.00
        $this->assertEquals('500.00', $result->totalAmount);
        $this->assertEquals(1, $result->totalCount);
    }

}
