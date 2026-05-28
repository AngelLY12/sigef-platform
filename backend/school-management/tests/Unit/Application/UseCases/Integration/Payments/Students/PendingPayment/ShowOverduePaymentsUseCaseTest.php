<?php

namespace Tests\Unit\Application\UseCases\Integration\Payments\Students\PendingPayment;

use App\Core\Application\DTO\Response\PaymentConcept\PendingPaymentConceptsResponse;
use App\Core\Application\UseCases\Payments\Student\PendingPayment\ShowOverduePaymentsUseCase;
use App\Core\Domain\Enum\Payment\PaymentStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Infraestructure\Mappers\UserMapper;
use App\Models\Career as CareerModel;
use App\Models\StudentDetail as StudentDetailModel;
use Carbon\Carbon;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use App\Models\User as UserModel;
use App\Models\PaymentConcept as PaymentConceptModel;
use App\Models\Payment as PaymentModel;

class ShowOverduePaymentsUseCaseTest extends TestCase
{
    use DatabaseTransactions;

    private ShowOverduePaymentsUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        $this->useCase = app(ShowOverduePaymentsUseCase::class);
    }

    #[Test]
    public function it_returns_empty_array_when_no_overdue_payments(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
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
    public function it_returns_overdue_payments_for_finished_concepts(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto FINALIZADO con fecha de fin pasada (vencido)
        $overdueConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Matrícula Vencida',
            'amount' => '1000.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::FINALIZADO->value,
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(5), // Fecha pasada = vencido
        ]);

        // Concepto ACTIVO no debe aparecer
        PaymentConceptModel::factory()->create([
            'concept_name' => 'Matrícula Activa',
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'start_date' => Carbon::now()->subDays(10),
            'end_date' => Carbon::now()->addDays(20),
        ]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertCount(1, $result);
        $this->assertInstanceOf(PendingPaymentConceptsResponse::class, $result[0]);
        $this->assertEquals('Matrícula Vencida', $result[0]->concept_name);
        $this->assertEquals('1000.00', $result[0]->amount);
    }

    #[Test]
    public function it_excludes_paid_payments(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto vencido
        $overdueConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto Vencido',
            'amount' => '800.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::FINALIZADO->value,
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(5),
        ]);

        // Pago COMPLETADO para este concepto
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $overdueConcept->id,
            'amount' => '800.00',
            'amount_received' => '800.00',
            'status' => PaymentStatus::SUCCEEDED->value,
        ]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_includes_underpaid_payments(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto vencido
        $overdueConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto Subpagado',
            'amount' => '1000.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::FINALIZADO->value,
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(5),
        ]);

        // Pago SUBPAGADO (UNDERPAID)
        PaymentModel::factory()->create([
            'user_id' => $user->id,
            'payment_concept_id' => $overdueConcept->id,
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
    public function it_excludes_concepts_with_user_exceptions(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto vencido con excepción para este usuario
        $overdueConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto con Excepción',
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::FINALIZADO->value,
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(5),
        ]);

        $overdueConcept->exceptions()->sync([$user->id]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_filters_by_career_when_user_has_career(): void
    {
        // Arrange
        $career = CareerModel::factory()->create(['career_name' => 'Ingeniería de Sistemas']);

        $user = UserModel::factory()->asStudent()->create();
        StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id,
            'semestre' => 5,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load(['roles', 'studentDetail']);
        $userEntity = UserMapper::toDomain($user);

        // Concepto vencido para ESTA carrera
        $careerConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto por Carrera',
            'amount' => '700.00',
            'applies_to' => PaymentConceptAppliesTo::CARRERA->value,
            'status' => PaymentConceptStatus::FINALIZADO->value,
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(5),
        ]);
        $careerConcept->careers()->attach($career->id);

        // Concepto vencido para OTRA carrera (no debe aparecer)
        $otherCareer = CareerModel::factory()->create(['career_name' => 'Medicina']);
        $otherCareerConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto Otra Carrera',
            'amount' => '900.00',
            'applies_to' => PaymentConceptAppliesTo::CARRERA->value,
            'status' => PaymentConceptStatus::FINALIZADO->value,
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(5),
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

        $user = UserModel::factory()->asStudent()->create();
        StudentDetailModel::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id,
            'semestre' => 3,
        ]);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load(['roles', 'studentDetail']);
        $userEntity = UserMapper::toDomain($user);

        // Concepto vencido para semestre 3
        $semesterConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto por Semestre',
            'amount' => '600.00',
            'applies_to' => PaymentConceptAppliesTo::SEMESTRE->value,
            'status' => PaymentConceptStatus::FINALIZADO->value,
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(5),
        ]);
        $semesterConcept->paymentConceptSemesters()->create(['semestre' => 3]);

        // Concepto vencido para semestre 5 (no debe aparecer)
        $otherSemesterConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto Otro Semestre',
            'amount' => '800.00',
            'applies_to' => PaymentConceptAppliesTo::SEMESTRE->value,
            'status' => PaymentConceptStatus::FINALIZADO->value,
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(5),
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
        ]);

        $applicantRole = Role::where('name', UserRoles::APPLICANT->value)->firstOrFail();
        $user->assignRole($applicantRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Concepto vencido para aplicantes
        $applicantConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto para Aplicantes',
            'amount' => '300.00',
            'applies_to' => PaymentConceptAppliesTo::TAG->value,
            'status' => PaymentConceptStatus::FINALIZADO->value,
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(5),
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
    public function it_shows_only_latest_concepts_first(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Conceptos vencidos creados en diferentes momentos
        $oldConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto Antiguo',
            'amount' => '100.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::FINALIZADO->value,
            'start_date' => Carbon::now()->subDays(60),
            'end_date' => Carbon::now()->subDays(30),
            'created_at' => Carbon::now()->subDays(10),
        ]);

        $newConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto Reciente',
            'amount' => '200.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::FINALIZADO->value,
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(5),
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
        ]);
        $userEntity = UserMapper::toDomain($user);

        // Concepto para TODOS (requiere rol estudiante)
        PaymentConceptModel::factory()->create([
            'concept_name' => 'Concepto para Estudiantes',
            'amount' => '500.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::FINALIZADO->value,
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(5),
        ]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertEmpty($result);
    }

    #[Test]
    public function it_includes_multiple_overdue_payments(): void
    {
        // Arrange
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        // Crear varios conceptos vencidos
        PaymentConceptModel::factory()->count(3)->create([
            'concept_name' => fn($i) => "Concepto Vencido"  ,
            'amount' => fn($i) => '100.00',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::FINALIZADO->value,
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(5),
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
        $user = UserModel::factory()->asStudent()->create();
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $user->assignRole($studentRole);
        $user->load('roles');
        $userEntity = UserMapper::toDomain($user);

        $overdueConcept = PaymentConceptModel::factory()->create([
            'concept_name' => 'Test Concept',
            'description' => 'Test Description',
            'amount' => '750.50',
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
            'status' => PaymentConceptStatus::FINALIZADO->value,
            'start_date' => Carbon::now()->subDays(30),
            'end_date' => Carbon::now()->subDays(5),
        ]);

        // Act
        $result = $this->useCase->execute($userEntity);

        // Assert
        $this->assertCount(1, $result);
        $response = $result[0];

        $this->assertEquals($overdueConcept->id, $response->id);
        $this->assertEquals('Test Concept', $response->concept_name);
        $this->assertEquals('Test Description', $response->description);
        $this->assertEquals('750.50', $response->amount);
        $this->assertNotNull($response->start_date);
        $this->assertNotNull($response->end_date);
    }

}
