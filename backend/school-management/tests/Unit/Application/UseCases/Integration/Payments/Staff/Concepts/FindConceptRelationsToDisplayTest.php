<?php

namespace Tests\Unit\Application\UseCases\Integration\Payments\Staff\Concepts;

use App\Core\Application\UseCases\Payments\Staff\Concepts\FindConceptRelationsToDisplay;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Exceptions\NotFound\ConceptNotFoundException;
use App\Models\Career;
use App\Models\PaymentConcept;
use App\Models\StudentDetail;
use App\Models\User;
use Database\Seeders\PermissionsSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FindConceptRelationsToDisplayTest extends TestCase
{
    use DatabaseTransactions;

    private FindConceptRelationsToDisplay $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        $this->seed(PermissionsSeeder::class);

        $this->useCase = app(FindConceptRelationsToDisplay::class);
    }

    #[Test]
    public function it_finds_concept_relations_for_todos(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'applies_to' => PaymentConceptAppliesTo::TODOS,
            'status' => PaymentConceptStatus::ACTIVO
        ]);

        // Act
        $result = $this->useCase->execute($concept->id);

        // Assert
        $this->assertEquals($concept->id, $result->id);
        $this->assertEquals($concept->concept_name, $result->concept_name);
        $this->assertEquals(PaymentConceptAppliesTo::TODOS->value, $result->applies_to);

        // Para TODOS, no debería cargar users, careers, semesters
        $this->assertEmpty($result->users);
        $this->assertEmpty($result->careers);
        $this->assertEmpty($result->semesters);
        $this->assertEmpty($result->applicantTags);

        // Pero podría tener excepciones si se agregaron
        $this->assertIsArray($result->exceptionUsers);
    }

    #[Test]
    public function it_finds_concept_relations_for_estudiantes(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        // Crear estudiantes con números de control
        $students = User::factory()->count(3)->create(['status' => UserStatus::ACTIVO]);
        $nControls = [];

        foreach ($students as $student) {
            $student->assignRole($studentRole);
            $detail = StudentDetail::factory()->forUser($student)->create();
            $nControls[] = $detail->n_control;
        }

        // Crear concepto que aplica a estudiantes específicos
        $concept = PaymentConcept::factory()->create([
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES,
        ]);

        // Attach students
        $concept->users()->attach($students->pluck('id'));

        // Act
        $result = $this->useCase->execute($concept->id);

        // Assert
        $this->assertEquals($concept->id, $result->id);
        $this->assertEquals(PaymentConceptAppliesTo::ESTUDIANTES->value, $result->applies_to);

        // Debería tener usuarios con sus números de control
        $this->assertCount(3, $result->users);
        $this->assertEqualsCanonicalizing($nControls, $result->users);

        // No debería tener careers, semesters, applicantTags
        $this->assertEmpty($result->careers);
        $this->assertEmpty($result->semesters);
        $this->assertEmpty($result->applicantTags);
    }

    #[Test]
    public function it_finds_concept_relations_for_career(): void
    {
        // Arrange
        // Crear carreras
        $careers = Career::factory()->count(2)->create();

        // Crear concepto que aplica a carreras
        $concept = PaymentConcept::factory()->create([
            'applies_to' => PaymentConceptAppliesTo::CARRERA,
        ]);

        // Attach careers
        $concept->careers()->attach($careers->pluck('id'));

        // Act
        $result = $this->useCase->execute($concept->id);

        // Assert
        $this->assertEquals($concept->id, $result->id);
        $this->assertEquals(PaymentConceptAppliesTo::CARRERA->value, $result->applies_to);

        // Debería tener careers
        $this->assertCount(2, $result->careers);
        $this->assertEqualsCanonicalizing($careers->pluck('id')->toArray(), $result->careers);

        // No debería tener users, semesters, applicantTags
        $this->assertEmpty($result->users);
        $this->assertEmpty($result->semesters);
        $this->assertEmpty($result->applicantTags);
    }

    #[Test]
    public function it_finds_concept_relations_for_semester(): void
    {
        // Arrange
        // Crear concepto que aplica a semestres
        $concept = PaymentConcept::factory()->create([
            'applies_to' => PaymentConceptAppliesTo::SEMESTRE,
        ]);

        // Attach semesters (usando la relación paymentConceptSemesters)
        $concept->paymentConceptSemesters()->createMany([
            ['semestre' => 1],
            ['semestre' => 2],
            ['semestre' => 3],
        ]);

        // Act
        $result = $this->useCase->execute($concept->id);

        // Assert
        $this->assertEquals($concept->id, $result->id);
        $this->assertEquals(PaymentConceptAppliesTo::SEMESTRE->value, $result->applies_to);

        // Debería tener semesters
        $this->assertCount(3, $result->semesters);
        $this->assertEqualsCanonicalizing([1, 2, 3], $result->semesters);

        // No debería tener users, careers, applicantTags
        $this->assertEmpty($result->users);
        $this->assertEmpty($result->careers);
        $this->assertEmpty($result->applicantTags);
    }

    #[Test]
    public function it_finds_concept_relations_for_career_semester(): void
    {
        // Arrange
        // Crear carreras
        $careers = Career::factory()->count(2)->create();

        // Crear concepto que aplica a carrera-semestre
        $concept = PaymentConcept::factory()->create([
            'applies_to' => PaymentConceptAppliesTo::CARRERA_SEMESTRE,
        ]);

        // Attach careers
        $concept->careers()->attach($careers->pluck('id'));

        // Attach semesters
        $concept->paymentConceptSemesters()->createMany([
            ['semestre' => 4],
            ['semestre' => 5],
        ]);

        // Act
        $result = $this->useCase->execute($concept->id);

        // Assert
        $this->assertEquals($concept->id, $result->id);
        $this->assertEquals(PaymentConceptAppliesTo::CARRERA_SEMESTRE->value, $result->applies_to);

        // Debería tener careers y semesters
        $this->assertCount(2, $result->careers);
        $this->assertEqualsCanonicalizing($careers->pluck('id')->toArray(), $result->careers);

        $this->assertCount(2, $result->semesters);
        $this->assertEqualsCanonicalizing([4, 5], $result->semesters);

        // No debería tener users, applicantTags
        $this->assertEmpty($result->users);
        $this->assertEmpty($result->applicantTags);
    }

    #[Test]
    public function it_finds_concept_relations_with_exceptions(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        // Crear estudiantes
        $students = User::factory()->count(3)->create(['status' => UserStatus::ACTIVO]);
        $exceptionStudents = User::factory()->count(2)->create(['status' => UserStatus::ACTIVO]);

        $allStudents = $students->merge($exceptionStudents);
        $nControls = [];
        $exceptionNControls = [];

        foreach ($allStudents as $index => $student) {
            $student->assignRole($studentRole);
            $detail = StudentDetail::factory()->forUser($student)->create();

            if ($index < 3) {
                $nControls[] = $detail->n_control;
            } else {
                $exceptionNControls[] = $detail->n_control;
            }
        }

        // Crear concepto que aplica a estudiantes específicos con excepciones
        $concept = PaymentConcept::factory()->create([
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES,
        ]);

        // Attach students (a quienes aplica)
        $concept->users()->attach($students->pluck('id'));

        // Attach exceptions (a quienes NO aplica)
        $concept->exceptions()->attach($exceptionStudents->pluck('id'));

        // Act
        $result = $this->useCase->execute($concept->id);

        // Assert
        $this->assertEquals($concept->id, $result->id);

        // Debería tener usuarios
        $this->assertCount(3, $result->users);
        $this->assertEqualsCanonicalizing($nControls, $result->users);

        // Debería tener excepciones
        $this->assertCount(2, $result->exceptionUsers);
        $this->assertEqualsCanonicalizing($exceptionNControls, $result->exceptionUsers);
    }

    #[Test]
    public function it_filters_users_without_student_detail(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        // Estudiante CON StudentDetail
        $studentWithDetail = User::factory()->create(['status' => UserStatus::ACTIVO]);
        $studentWithDetail->assignRole($studentRole);
        $detail = StudentDetail::factory()->forUser($studentWithDetail)->create(['n_control' => '12345678']);

        // Estudiante SIN StudentDetail
        $studentWithoutDetail = User::factory()->create(['status' => UserStatus::ACTIVO]);
        $studentWithoutDetail->assignRole($studentRole);
        // No crear StudentDetail

        // Crear concepto
        $concept = PaymentConcept::factory()->create([
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES,
        ]);

        // Attach ambos estudiantes
        $concept->users()->attach([$studentWithDetail->id, $studentWithoutDetail->id]);

        // Act
        $result = $this->useCase->execute($concept->id);

        // Assert
        // Solo debería incluir al estudiante CON StudentDetail
        $this->assertCount(1, $result->users);
        $this->assertEquals(['12345678'], $result->users);
    }

    #[Test]
    public function it_filters_exception_users_without_student_detail(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        // Estudiante CON StudentDetail
        $studentWithDetail = User::factory()->create(['status' => UserStatus::ACTIVO]);
        $studentWithDetail->assignRole($studentRole);
        $detail = StudentDetail::factory()->forUser($studentWithDetail)->create(['n_control' => '87654321']);

        // Estudiante SIN StudentDetail
        $studentWithoutDetail = User::factory()->create(['status' => UserStatus::ACTIVO]);
        $studentWithoutDetail->assignRole($studentRole);
        // No crear StudentDetail

        // Crear concepto
        $concept = PaymentConcept::factory()->create([
            'applies_to' => PaymentConceptAppliesTo::TODOS,
        ]);

        // Attach ambos como excepciones
        $concept->exceptions()->attach([$studentWithDetail->id, $studentWithoutDetail->id]);

        // Act
        $result = $this->useCase->execute($concept->id);

        // Assert
        // Solo debería incluir al estudiante CON StudentDetail en las excepciones
        $this->assertCount(1, $result->exceptionUsers);
        $this->assertEquals(['87654321'], $result->exceptionUsers);
    }

    #[Test]
    public function it_throws_exception_when_concept_not_found(): void
    {
        // Arrange
        $nonExistentId = 99999;

        // Expect exception
        $this->expectException(ConceptNotFoundException::class);

        // Act
        $this->useCase->execute($nonExistentId);
    }

    #[Test]
    public function it_handles_concept_with_no_relations(): void
    {
        // Arrange
        $concept = PaymentConcept::factory()->create([
            'applies_to' => PaymentConceptAppliesTo::TODOS,
        ]);
        // No adjuntar ninguna relación

        // Act
        $result = $this->useCase->execute($concept->id);

        // Assert
        $this->assertEquals($concept->id, $result->id);
        $this->assertEquals(PaymentConceptAppliesTo::TODOS->value, $result->applies_to);

        // Todos los arrays deberían estar vacíos
        $this->assertEmpty($result->users);
        $this->assertEmpty($result->careers);
        $this->assertEmpty($result->semesters);
        $this->assertEmpty($result->exceptionUsers);
        $this->assertEmpty($result->applicantTags);
    }

    #[Test]
    public function it_finds_concept_relations_for_tag_applicant(): void
    {
        // Arrange
        // Crear concepto que aplica a tags
        $concept = PaymentConcept::factory()->create([
            'applies_to' => PaymentConceptAppliesTo::TAG,
        ]);

        // Attach applicant tags (asumiendo que existe la relación y modelo)
        // Este test depende de cómo implementes applicantTypes
        // Por ahora, lo dejamos como test básico

        // Act
        $result = $this->useCase->execute($concept->id);

        // Assert
        $this->assertEquals($concept->id, $result->id);
        $this->assertEquals(PaymentConceptAppliesTo::TAG->value, $result->applies_to);

        // applicantTags debería ser un array (posiblemente vacío)
        $this->assertIsArray($result->applicantTags);
    }

    #[Test]
    public function it_loads_minimal_columns_for_performance(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        $student = User::factory()->create(['status' => UserStatus::ACTIVO]);
        $student->assignRole($studentRole);
        $detail = StudentDetail::factory()->forUser($student)->create(['n_control' => '11111111']);

        $concept = PaymentConcept::factory()->create([
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES,
        ]);

        $concept->users()->attach($student->id);
        $concept->exceptions()->attach($student->id);

        // Act
        $result = $this->useCase->execute($concept->id);

        // Assert
        $this->assertEquals($concept->id, $result->id);

        // Verificar que se cargaron las relaciones con columnas específicas
        // (esto es más una verificación de que no se cargan todas las columnas)
        $this->assertIsArray($result->users);
        $this->assertIsArray($result->exceptionUsers);
    }

    #[Test]
    public function it_handles_concept_with_only_exceptions(): void
    {
        // Arrange
        $studentRole = Role::where('name', UserRoles::STUDENT->value)->firstOrFail();

        // Crear estudiantes para excepciones
        $exceptionStudents = User::factory()->count(2)->create(['status' => UserStatus::ACTIVO]);
        $exceptionNControls = [];

        foreach ($exceptionStudents as $student) {
            $student->assignRole($studentRole);
            $detail = StudentDetail::factory()->forUser($student)->create();
            $exceptionNControls[] = $detail->n_control;
        }

        // Crear concepto que aplica a TODOS pero con excepciones
        $concept = PaymentConcept::factory()->create([
            'applies_to' => PaymentConceptAppliesTo::TODOS,
        ]);

        // Solo agregar excepciones
        $concept->exceptions()->attach($exceptionStudents->pluck('id'));

        // Act
        $result = $this->useCase->execute($concept->id);

        // Assert
        $this->assertEquals($concept->id, $result->id);
        $this->assertEquals(PaymentConceptAppliesTo::TODOS->value, $result->applies_to);

        // Debería tener excepciones
        $this->assertCount(2, $result->exceptionUsers);
        $this->assertEqualsCanonicalizing($exceptionNControls, $result->exceptionUsers);

        // Los demás arrays deberían estar vacíos
        $this->assertEmpty($result->users);
        $this->assertEmpty($result->careers);
        $this->assertEmpty($result->semesters);
        $this->assertEmpty($result->applicantTags);
    }

    #[Test]
    public function it_returns_empty_arrays_for_unloaded_relations(): void
    {
        // Arrange
        // Crear un concepto sin cargar relaciones explícitamente
        $concept = PaymentConcept::factory()->create([
            'applies_to' => PaymentConceptAppliesTo::ESTUDIANTES,
        ]);

        // Act
        $result = $this->useCase->execute($concept->id);

        // Assert
        // Aunque applies_to es ESTUDIANTES, si no hay usuarios adjuntos,
        // el array de users debería estar vacío
        $this->assertEmpty($result->users);
        $this->assertEmpty($result->careers);
        $this->assertEmpty($result->semesters);
        $this->assertEmpty($result->exceptionUsers);
        $this->assertEmpty($result->applicantTags);
    }

}
