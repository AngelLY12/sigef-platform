<?php

namespace Tests\Unit\Application\UseCases\Integration\Payments\Staff\Concepts;

use App\Core\Application\DTO\Request\PaymentConcept\CreatePaymentConceptDTO;
use App\Core\Application\DTO\Response\PaymentConcept\CreatePaymentConceptResponse;
use App\Core\Application\UseCases\Payments\Staff\Concepts\CreatePaymentConceptUseCase;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Exceptions\NotFound\CareersNotFoundException;
use App\Exceptions\NotFound\RecipientsNotFoundException;
use App\Exceptions\NotFound\StudentsNotFoundException;
use App\Exceptions\Validation\CareerSemesterInvalidException;
use App\Exceptions\Validation\RequiredForAppliesToException;
use App\Exceptions\Validation\SemestersNotFoundException;
use App\Models\Career;
use App\Models\PaymentConcept;
use App\Models\StudentDetail;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreatePaymentConceptUseCaseTest extends TestCase
{
    use DatabaseTransactions;

    private CreatePaymentConceptUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        $this->seed(PermissionsSeeder::class);

        Event::fake();
        Bus::fake();

        $this->useCase = app(CreatePaymentConceptUseCase::class);
    }

    #[Test]
    public function it_creates_payment_concept_for_all_students(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        // Crear algunos estudiantes con StudentDetail
        $students = User::factory()->count(5)->create(['status' => UserStatus::ACTIVO]);
        foreach ($students as $student) {
            $student->assignRole($studentRole);
            StudentDetail::factory()->forUser($student)->create();
        }

        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Matrícula General',
            amount: '1000.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::TODOS,
            description: 'Matrícula para todos los estudiantes',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            semesters: null,
            careers: null,
            students: null,
            exceptionStudents: null,
            applicantTags: null
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(CreatePaymentConceptResponse::class, $response);
        $this->assertEquals('Matrícula General', $response->conceptName);
        $this->assertEquals('1000.00', $response->amount);
        $this->assertEquals(PaymentConceptStatus::ACTIVO->value, $response->status);
        $this->assertEquals(PaymentConceptAppliesTo::TODOS->value, $response->appliesTo);
        $this->assertEquals(5, $response->affectedStudentsCount);

        // Verificar que se creó en la base de datos
        $concept = PaymentConcept::find($response->id);
        $this->assertNotNull($concept);
        $this->assertEquals('Matrícula General', $concept->concept_name);

        // Verificar evento
        Event::assertDispatched(\App\Events\PaymentConceptCreated::class);
    }

    #[Test]
    public function it_creates_payment_concept_for_specific_students(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        // Crear estudiantes específicos con StudentDetail
        $students = User::factory()->count(3)->create(['status' => UserStatus::ACTIVO]);
        $studentNumControls = [];
        foreach ($students as $student) {
            $student->assignRole($studentRole);
            $details=StudentDetail::factory()->forUser($student)->create();
            $studentNumControls[] = $details->n_control;
        }

        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Pago Especial',
            amount: '500.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::ESTUDIANTES,
            description: 'Pago para estudiantes específicos',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addYear(),
            semesters: null,
            careers: null,
            students: $studentNumControls,
            exceptionStudents: null,
            applicantTags: null
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(CreatePaymentConceptResponse::class, $response);
        $this->assertEquals('Pago Especial', $response->conceptName);
        $this->assertEquals(PaymentConceptAppliesTo::ESTUDIANTES->value, $response->appliesTo);
        $this->assertEquals(3, $response->affectedStudentsCount);

        // Verificar evento
        Event::assertDispatched(\App\Events\PaymentConceptCreated::class);
    }

    #[Test]
    public function it_creates_payment_concept_for_career(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        // Crear carrera
        $career = Career::factory()->create(['career_name' => 'Ingeniería']);

        // Crear estudiantes en la carrera con StudentDetail
        $students = User::factory()->count(4)->create(['status' => UserStatus::ACTIVO]);
        foreach ($students as $student) {
            $student->assignRole($studentRole);
            StudentDetail::factory()->forUser($student)->forCareer($career)->create();
        }

        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Pago de Carrera',
            amount: '750.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::CARRERA,
            description: 'Pago para estudiantes de Ingeniería',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addYear(),
            semesters: null,
            careers: [$career->id],
            students: null,
            exceptionStudents: null,
            applicantTags: null
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(CreatePaymentConceptResponse::class, $response);
        $this->assertEquals('Pago de Carrera', $response->conceptName);
        $this->assertEquals(PaymentConceptAppliesTo::CARRERA->value, $response->appliesTo);
        $this->assertEquals(4, $response->affectedStudentsCount);
    }

    #[Test]
    public function it_creates_payment_concept_for_semester(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        // Crear estudiantes con semestre específico
        $students = User::factory()->count(3)->create(['status' => UserStatus::ACTIVO]);
        foreach ($students as $student) {
            $student->assignRole($studentRole);
            StudentDetail::factory()->forUser($student)->state([
                'semestre' => 5 // Semestre específico
            ])->create();
        }

        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Pago de Semestre 5',
            amount: '800.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::SEMESTRE,
            description: 'Pago para estudiantes del semestre 5',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonths(6),
            semesters: [5], // Semestre específico
            careers: null,
            students: null,
            exceptionStudents: null,
            applicantTags: null
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(CreatePaymentConceptResponse::class, $response);
        $this->assertEquals('Pago de Semestre 5', $response->conceptName);
        $this->assertEquals(PaymentConceptAppliesTo::SEMESTRE->value, $response->appliesTo);
        $this->assertEquals(3, $response->affectedStudentsCount);
    }

    #[Test]
    public function it_creates_payment_concept_for_career_semester(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        $career = Career::factory()->create(['career_name' => 'Medicina']);

        // Estudiantes que coinciden con carrera Y semestre
        $students = User::factory()->count(2)->create(['status' => UserStatus::ACTIVO]);
        foreach ($students as $student) {
            $student->assignRole($studentRole);
            StudentDetail::factory()->forUser($student)
                ->forCareer($career)
                ->state(['semestre' => 3])
                ->create();
        }

        // Otro estudiante que NO coincide (diferente semestre)
        $otherStudent = User::factory()->create(['status' => UserStatus::ACTIVO]);
        $otherStudent->assignRole($studentRole);
        StudentDetail::factory()->forUser($otherStudent)
            ->forCareer($career)
            ->state(['semestre' => 5]) // Diferente semestre
            ->create();

        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Pago Carrera-Semestre',
            amount: '1200.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::CARRERA_SEMESTRE,
            description: 'Pago para Medicina semestre 3',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            semesters: [3],
            careers: [$career->id],
            students: null,
            exceptionStudents: null,
            applicantTags: null
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(CreatePaymentConceptResponse::class, $response);
        $this->assertEquals('Pago Carrera-Semestre', $response->conceptName);
        $this->assertEquals(PaymentConceptAppliesTo::CARRERA_SEMESTRE->value, $response->appliesTo);
        $this->assertEquals(2, $response->affectedStudentsCount); // Solo los 2 con carrera y semestre correctos
    }

    #[Test]
    public function it_creates_payment_concept_with_exception_students(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        // Crear 5 estudiantes
        $allStudents = User::factory()->count(5)->create(['status' => UserStatus::ACTIVO]);
        $studentNumControls = [];
        foreach ($allStudents as $student) {
            $student->assignRole($studentRole);
            $detail=StudentDetail::factory()->forUser($student)->create();
            $studentNumControls[] = $detail->n_control;
        }

        // Los primeros 2 son excepciones
        $exceptionNumControl = array_slice($studentNumControls, 0, 2);

        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Pago con Excepciones',
            amount: '300.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::TODOS,
            description: 'Pago para todos excepto algunos',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addYear(),
            semesters: null,
            careers: null,
            students:null,
            exceptionStudents: $exceptionNumControl,
            applicantTags: null
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(CreatePaymentConceptResponse::class, $response);
        $this->assertEquals('Pago con Excepciones', $response->conceptName);
        $this->assertEquals(3, $response->affectedStudentsCount); // 5 total - 2 excepciones = 3
    }

    #[Test]
    public function it_triggers_administration_event_for_high_amount(): void
    {
        // Arrange
        config(['concepts.amount.notifications.threshold' => '1000.00']);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $students = User::factory()->count(2)->create(['status' => UserStatus::ACTIVO]);
        foreach ($students as $student) {
            $student->assignRole($studentRole);
            StudentDetail::factory()->forUser($student)->create();
        }

        // Monto mayor al threshold (1000.00)
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Pago Alto',
            amount: '1500.00', // > 1000.00
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::TODOS,
            description: 'Pago con monto alto',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            semesters: null,
            careers: null,
            students: null,
            exceptionStudents: null,
            applicantTags: null
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(CreatePaymentConceptResponse::class, $response);

        // Verificar que se disparó el evento de administración
        Event::assertDispatched(\App\Events\AdministrationEvent::class, function ($event) {
            return $event->amount === '1500.00' && $event->action === 'creó';
        });
    }

    #[Test]
    public function it_does_not_trigger_administration_event_for_low_amount(): void
    {
        // Arrange
        config(['concepts.amount.notifications.threshold' => '1000.00']);

        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $students = User::factory()->count(2)->create(['status' => UserStatus::ACTIVO]);
        foreach ($students as $student) {
            $student->assignRole($studentRole);
            StudentDetail::factory()->forUser($student)->create();
        }

        // Monto menor al threshold (1000.00)
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Pago Bajo',
            amount: '500.00', // < 1000.00
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::TODOS,
            description: 'Pago con monto bajo',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            semesters: null,
            careers: null,
            students: null,
            exceptionStudents: null,
            applicantTags: null
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(CreatePaymentConceptResponse::class, $response);

        // Verificar que NO se disparó el evento de administración
        Event::assertNotDispatched(\App\Events\AdministrationEvent::class);
    }

    #[Test]
    public function it_throws_exception_when_no_recipients_found(): void
    {
        // Arrange - No crear estudiantes
        User::role(UserRoles::STUDENT->value)->delete();

        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Concepto sin Destinatarios',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::TODOS,
            description: 'Este concepto no debería crearse',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            semesters: null,
            careers: null,
            students: null,
            exceptionStudents: null,
            applicantTags: null
        );

        // Expect exception
        $this->expectException(RecipientsNotFoundException::class);

        // Act
        $this->useCase->execute($dto);
    }

    #[Test]
    public function it_throws_exception_when_career_not_found(): void
    {
        // Arrange
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Concepto sin Carrera',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::CARRERA,
            description: 'Falta especificar carrera',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            semesters: null,
            careers: null, // ¡Falta carrera!
            students: null,
            exceptionStudents: null,
            applicantTags: null
        );

        // Expect exception
        $this->expectException(RequiredForAppliesToException::class);

        // Act
        $this->useCase->execute($dto);
    }

    #[Test]
    public function it_throws_exception_when_semester_not_found(): void
    {
        // Arrange
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Concepto sin Semestre',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::SEMESTRE,
            description: 'Falta especificar semestre',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            semesters: null, // ¡Falta semestre!
            careers: null,
            students: null,
            exceptionStudents: null,
            applicantTags: null
        );

        // Expect exception
        $this->expectException(RequiredForAppliesToException::class);

        // Act
        $this->useCase->execute($dto);
    }

    #[Test]
    public function it_throws_exception_when_students_not_found(): void
    {
        // Arrange
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Concepto sin Estudiantes',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::ESTUDIANTES,
            description: 'Falta especificar estudiantes',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            semesters: null,
            careers: null,
            students: null, // ¡Falta estudiantes!
            exceptionStudents: null,
            applicantTags: null
        );

        // Expect exception
        $this->expectException(RequiredForAppliesToException::class);

        // Act
        $this->useCase->execute($dto);
    }

    #[Test]
    public function it_throws_exception_when_career_semester_invalid(): void
    {
        // Arrange
        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Concepto sin Carrera-Semestre',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::CARRERA_SEMESTRE,
            description: 'Falta carrera o semestre',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            semesters: null, // ¡Falta!
            careers: null, // ¡Falta!
            students: null,
            exceptionStudents: null,
            applicantTags: null
        );

        // Expect exception
        $this->expectException(RequiredForAppliesToException::class);

        // Act
        $this->useCase->execute($dto);
    }

    #[Test]
    public function it_creates_inactive_concept(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $students = User::factory()->count(2)->create(['status' => UserStatus::ACTIVO]);
        foreach ($students as $student) {
            $student->assignRole($studentRole);
            StudentDetail::factory()->forUser($student)->create();
        }

        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Concepto Inactivo',
            amount: '200.00',
            status: PaymentConceptStatus::DESACTIVADO, // Inactivo
            appliesTo: PaymentConceptAppliesTo::TODOS,
            description: 'Concepto creado inactivo',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            semesters: null,
            careers: null,
            students: null,
            exceptionStudents: null,
            applicantTags: null
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(CreatePaymentConceptResponse::class, $response);
        $this->assertEquals(PaymentConceptStatus::DESACTIVADO->value, $response->status);

        // Verificar en BD
        $concept = PaymentConcept::find($response->id);
        $this->assertEquals(PaymentConceptStatus::DESACTIVADO, $concept->status);
    }

    #[Test]
    public function it_creates_concept_without_description(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();
        $students = User::factory()->count(1)->create(['status' => UserStatus::ACTIVO]);
        foreach ($students as $student) {
            $student->assignRole($studentRole);
            StudentDetail::factory()->forUser($student)->create();
        }

        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Concepto sin Descripción',
            amount: '100.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::TODOS,
            description: null,
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            semesters: null,
            careers: null,
            students: null,
            exceptionStudents: null,
            applicantTags: null
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(CreatePaymentConceptResponse::class, $response);
        $this->assertEquals('Concepto sin Descripción', $response->conceptName);
        $this->assertNull($response->description);
    }

    #[Test]
    public function it_creates_concept_with_multiple_careers(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        // Crear 2 carreras
        $career1 = Career::factory()->create(['career_name' => 'Ingeniería']);
        $career2 = Career::factory()->create(['career_name' => 'Medicina']);

        // Estudiantes en carrera 1
        $students1 = User::factory()->count(2)->create(['status' => UserStatus::ACTIVO]);
        foreach ($students1 as $student) {
            $student->assignRole($studentRole);
            StudentDetail::factory()->forUser($student)->forCareer($career1)->create();
        }

        // Estudiantes en carrera 2
        $students2 = User::factory()->count(3)->create(['status' => UserStatus::ACTIVO]);
        foreach ($students2 as $student) {
            $student->assignRole($studentRole);
            StudentDetail::factory()->forUser($student)->forCareer($career2)->create();
        }

        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Pago Multi-Carrera',
            amount: '600.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::CARRERA,
            description: 'Pago para múltiples carreras',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            semesters: null,
            careers: [$career1->id, $career2->id],
            students: null,
            exceptionStudents: null,
            applicantTags: null
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(CreatePaymentConceptResponse::class, $response);
        $this->assertEquals('Pago Multi-Carrera', $response->conceptName);
        $this->assertEquals(5, $response->affectedStudentsCount); // 2 + 3
    }

    #[Test]
    public function it_creates_concept_with_multiple_semesters(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        // Estudiantes en semestre 1
        $students1 = User::factory()->count(2)->create(['status' => UserStatus::ACTIVO]);
        foreach ($students1 as $student) {
            $student->assignRole($studentRole);
            StudentDetail::factory()->forUser($student)->state(['semestre' => 1])->create();
        }

        // Estudiantes en semestre 2
        $students2 = User::factory()->count(3)->create(['status' => UserStatus::ACTIVO]);
        foreach ($students2 as $student) {
            $student->assignRole($studentRole);
            StudentDetail::factory()->forUser($student)->state(['semestre' => 2])->create();
        }

        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Pago Multi-Semestre',
            amount: '700.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::SEMESTRE,
            description: 'Pago para múltiples semestres',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            semesters: [1, 2],
            careers: null,
            students: null,
            exceptionStudents: null,
            applicantTags: null
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(CreatePaymentConceptResponse::class, $response);
        $this->assertEquals('Pago Multi-Semestre', $response->conceptName);
        $this->assertEquals(5, $response->affectedStudentsCount); // 2 + 3
    }

    #[Test]
    public function it_handles_students_without_student_detail(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        // Estudiante con StudentDetail
        $studentWithDetail = User::factory()->create(['status' => UserStatus::ACTIVO]);
        $studentWithDetail->assignRole($studentRole);
        StudentDetail::factory()->forUser($studentWithDetail)->create();

        $studentWithoutDetail = User::factory()->create(['status' => UserStatus::ACTIVO]);
        $studentWithoutDetail->assignRole($studentRole);
        // No creamos StudentDetail

        $dto = new CreatePaymentConceptDTO(
            concept_name: 'Pago para Estudiantes',
            amount: '200.00',
            status: PaymentConceptStatus::ACTIVO,
            appliesTo: PaymentConceptAppliesTo::TODOS,
            description: 'Solo estudiantes con StudentDetail',
            start_date: Carbon::now(),
            end_date: Carbon::now()->addMonth(),
            semesters: null,
            careers: null,
            students: null,
            exceptionStudents: null,
            applicantTags: null
        );

        // Act
        $response = $this->useCase->execute($dto);

        // Assert
        $this->assertInstanceOf(CreatePaymentConceptResponse::class, $response);
        $this->assertEquals(2, $response->affectedStudentsCount);
    }

}
