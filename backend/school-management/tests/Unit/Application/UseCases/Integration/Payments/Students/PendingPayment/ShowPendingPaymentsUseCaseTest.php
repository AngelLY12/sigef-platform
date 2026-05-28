<?php

namespace Tests\Unit\Application\UseCases\Integration\Payments\Students\PendingPayment;

use App\Core\Application\DTO\Response\PaymentConcept\PendingPaymentConceptsResponse;
use App\Core\Application\UseCases\Payments\Student\PendingPayment\ShowPendingPaymentsUseCase;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Exceptions\Unauthorized\UserInactiveException;
use Carbon\Carbon;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use App\Models\User as UserModel;
use App\Models\PaymentConcept as PaymentConceptModel;
use App\Models\Payment as PaymentModel;
use App\Models\Career as CareerModel;
use App\Models\StudentDetail as StudentDetailModel;

class ShowPendingPaymentsUseCaseTest extends TestCase
{
    use DatabaseTransactions;

    private ShowPendingPaymentsUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        $this->useCase = app(ShowPendingPaymentsUseCase::class);
    }

    #[Test]
    public function it_throws_exception_for_inactive_user(): void
    {
        // Arrange
        $user = UserModel::factory()->create([
            'name' => 'Inactive User',
            'last_name' => 'Test',
            'email' => 'inactive@example.com',
            'status' => UserStatus::BAJA,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Expect exception
        $this->expectException(UserInactiveException::class);

        // Act
        $this->useCase->execute($userEntity);
    }

    #[Test]
    public function it_returns_empty_array_when_no_pending_payments(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'status' => UserStatus::ACTIVO, // Usuario activo
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_returns_pending_payments_for_active_concepts(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'status' => UserStatus::ACTIVO,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto ACTIVO dentro de rango de fechas
        $pendingConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Matrícula Pendiente',
            'amount' => '1000.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20), // Fecha futura = pendiente
        ]);

        // Concepto DESACTIVADO no debe aparecer
        PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto Desactivado',
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::DESACTIVADO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Concepto FINALIZADO no debe aparecer
        PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto Finalizado',
            'amount' => '300.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::FINALIZADO->value,
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(5),
        ]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertCount(1, $result);
        $this->assertInstanceOf(PendingPaymentConceptsResponse::class, $result[0]);
        $this->assertEquals('Matrícula Pendiente', $result[0]->concept_name);
        $this->assertEquals('1000.00', $result[0]->amount);
    }

    #[Test]
    public function it_excludes_concepts_with_future_start_date(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'status' => UserStatus::ACTIVO,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto con fecha de inicio FUTURA (no debe aparecer aún)
        PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto Futuro',
            'amount' => '800.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->addDays(5), // Fecha futura
            'end_date' => Carbon::now()->addDays(30),
        ]);

        // Concepto con fecha de inicio PASADA (debe aparecer)
        PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto Vigente',
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->addDays(25),
        ]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Concepto Vigente', $result[0]->concept_name);
    }

    #[Test]
    public function it_excludes_paid_payments(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'status' => UserStatus::ACTIVO,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto activo
        $concept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto Pagado',
            'amount' => '800.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Pago COMPLETADO para este concepto
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept->id,
            'amount' => '800.00',
            'amount_received' => '800.00',
            'status' => PaymentStatus::SUCCEEDED->value,
        ]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertEmpty($result); // No debe aparecer porque ya está pagado
    }

    #[Test]
    public function it_includes_underpaid_payments(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'status' => UserStatus::ACTIVO,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto activo
        $concept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto Subpagado',
            'amount' => '1000.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Pago SUBPAGADO (UNDERPAID)
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $concept->id,
            'amount' => '1000.00',
            'amount_received' => '400.00',
            'status' => PaymentStatus::UNDERPAID->value,
        ]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertCount(1, $result);
        $this->assertInstanceOf(PendingPaymentConceptsResponse::class, $result[0]);
        $this->assertEquals('Concepto Subpagado', $result[0]->concept_name);
        $this->assertEquals('600.00', $result[0]->amount); // 1000 - 400 = 600
    }

    #[Test]
    public function it_includes_pending_payments_without_payment_record(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'status' => UserStatus::ACTIVO,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto activo SIN registro de pago
        PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto Nuevo',
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->addDays(25),
        ]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Concepto Nuevo', $result[0]->concept_name);
        $this->assertEquals('500.00', $result[0]->amount); // Monto completo
    }

    #[Test]
    public function it_excludes_concepts_with_user_exceptions(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'status' => UserStatus::ACTIVO,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto activo con excepción para este usuario
        $concept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto con Excepción',
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        $concept->exceptions()->sync([$user->id]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertEmpty($result); // No debe aparecer por la excepción
    }

    #[Test]
    public function it_filters_by_career_when_user_has_career(): void
    {
        // Arrange
        $career = CareerModel::factory()->create(['career_name' => 'Ingeniería de Sistemas']);

        $user = UserModel::factory()->asStudent()->create([
            'status' => UserStatus::ACTIVO,
        ]);

        StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id,
            'semestre' => 5,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load(['roles', 'studentDetail']);
        $userEntity = UserMapper::toDomain($user);

        // Concepto activo para ESTA carrera
        $careerConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto por Carrera',
            'amount' => '700.00',
            'applies_to' => PaymentConceptAppliesTo::CARRERA->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);
        $careerConcept->careers()->attach($career->id);

        // Concepto activo para OTRA carrera (no debe aparecer)
        $otherCareer = CareerModel::factory()->create(['career_name' => 'Medicina']);
        $otherCareerConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto Otra Carrera',
            'amount' => '900.00',
            'applies_to' => PaymentConceptAppliesTo::CARRERA->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);
        $otherCareerConcept->careers()->attach($otherCareer->id);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertCount(1, $result);
        $this->assertInstanceOf(PendingPaymentConceptsResponse::class, $result[0]);
        $this->assertEquals('Concepto por Carrera', $result[0]->concept_name);
    }

    #[Test]
    public function it_filters_by_semester_when_user_has_semester(): void
    {
        // Arrange
        $career = CareerModel::factory()->create(['career_name' => 'Ingeniería de Sistemas']);

        $user = UserModel::factory()->asStudent()->create([
            'status' => UserStatus::ACTIVO,
        ]);

        StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id,
            'semestre' => 3,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load(['roles', 'studentDetail']);
        $userEntity = UserMapper::toDomain($user);

        // Concepto activo para semestre 3
        $semesterConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto por Semestre',
            'amount' => '600.00',
            'applies_to' => PaymentConceptAppliesTo::SEMESTRE->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);
        $semesterConcept->paymentConceptSemesters()->create(['semestre' => 3]);

        // Concepto activo para semestre 5 (no debe aparecer)
        $otherSemesterConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto Otro Semestre',
            'amount' => '800.00',
            'applies_to' => PaymentConceptAppliesTo::SEMESTRE->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);
        $otherSemesterConcept->paymentConceptSemesters()->create(['semestre' => 5]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertCount(1, $result);
        $this->assertInstanceOf(PendingPaymentConceptsResponse::class, $result[0]);
        $this->assertEquals('Concepto por Semestre', $result[0]->concept_name);
    }

    #[Test]
    public function it_includes_concepts_for_applicants(): void
    {
        // Arrange
        $user = UserModel::factory()->create([
            'name' => 'Test Applicant',
            'last_name' => 'Test Last',
            'email' => 'applicant_' . uniqid() . '@example.com',
            'status' => UserStatus::ACTIVO,
        ]);

        $applicantRole = Role::where('name', UserRoles::APPLICANT->value)->firstOrFail();
        $user->assignRole($applicantRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto activo para aplicantes
        $applicantConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto para Aplicantes',
            'amount' => '300.00',
            'applies_to' => PaymentConceptAppliesTo::TAG->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);
        $applicantConcept->applicantTypes()->create([
            'tag' => \App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType::APPLICANT->value
        ]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertCount(1, $result);
        $this->assertInstanceOf(PendingPaymentConceptsResponse::class, $result[0]);
        $this->assertEquals('Concepto para Aplicantes', $result[0]->concept_name);
    }

    #[Test]
    public function it_shows_latest_concepts_first(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'status' => UserStatus::ACTIVO,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Conceptos creados en diferentes momentos
        $oldConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto Antiguo',
            'amount' => '100.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
            'created_at' => Carbon::now()->subDays(5),
        ]);

        $newConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto Reciente',
            'amount' => '200.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->addDays(25),
            'created_at' => Carbon::now()->subDays(1),
        ]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertCount(2, $result);
        $this->assertInstanceOf(PendingPaymentConceptsResponse::class, $result[0]);
        $this->assertInstanceOf(PendingPaymentConceptsResponse::class, $result[1]);

        // El más reciente debe estar primero
        $this->assertEquals('Concepto Reciente', $result[0]->concept_name);
        $this->assertEquals('Concepto Antiguo', $result[1]->concept_name);
    }

    #[Test]
    public function it_excludes_concepts_where_user_is_not_student(): void
    {
        // Arrange
        $user = UserModel::factory()->create([
            'name' => 'Non Student',
            'last_name' => 'User',
            'email' => 'nonstudent@example.com',
            'status' => UserStatus::ACTIVO,
        ]);
        $userEntity = UserMapper::toDomain($user);

        // Concepto para TODOS (requiere rol estudiante)
        PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto para Estudiantes',
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_includes_multiple_pending_payments(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'status' => UserStatus::ACTIVO,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Crear varios conceptos pendientes
        PaymentConceptModel::factory()->count(3)->create([
            'concept_name' => fn($i) => 'Concepto Pendiente ',
            'amount' => fn($i) => '100.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertCount(3, $result);
        foreach ($result as $item) {
            $this->assertInstanceOf(PendingPaymentConceptsResponse::class, $item);
        }
    }

    #[Test]
    public function it_returns_correct_data_structure(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'status' => UserStatus::ACTIVO,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $concept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Test Pending Concept',
            'description' => 'Test Description for Pending',
            'amount' => '750.50',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(5),
            'end_date' => Carbon::now()->addDays(25),
        ]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertCount(1, $result);
        $response = $result[0];

        $this->assertEquals($concept->id, $response->id);
        $this->assertEquals('Test Pending Concept', $response->concept_name);
        $this->assertEquals('Test Description for Pending', $response->description);
        $this->assertEquals('750.50', $response->amount);
        $this->assertNotNull($response->start_date);
        $this->assertNotNull($response->end_date);
    }

    #[Test]
    public function it_doesnt_includes_pending_payments_with_expired_end_date(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create([
            'status' => UserStatus::ACTIVO,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto ACTIVO con fecha de fin PASADA (sigue siendo pendiente porque está ACTIVO)
        $concept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto con Fecha Vencida pero Activo',
            'amount' => '600.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(1), // Fecha pasada pero estado ACTIVO
        ]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertCount(0, $result);
    }


}
