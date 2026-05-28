<?php

namespace Tests\Unit\Domain\Repositories\Command;

use Tests\Stubs\Repositories\Command\PaymentConceptRepStub;
use Tests\Unit\Domain\Repositories\BaseRepositoryTestCase;
use App\Core\Domain\Repositories\Command\Payments\PaymentConceptRepInterface;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Application\DTO\Response\User\UserIdListDTO;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PaymentConceptRepInterfaceTest extends BaseRepositoryTestCase
{
    /**
     * The interface class being implemented
     */
    protected string $interfaceClass = PaymentConceptRepInterface::class;

    /**
     * Setup the repository instance for testing
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Usamos un stub para probar el contrato
        $this->repository = new PaymentConceptRepStub();
    }

    /**
     * Test que el repositorio puede ser instanciado
     */
    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertNotNull($this->repository, 'El repositorio no está inicializado');
        $this->assertImplementsInterface($this->interfaceClass);
    }

    /**
     * Test que todos los métodos requeridos existen
     */
    #[Test]
    public function it_has_all_required_methods(): void
    {
        $this->assertNotNull($this->repository, 'El repositorio no está inicializado');

        $methods = [
            'create',
            'update',
            'deleteLogical',
            'delete',
            'attachToUsers',
            'attachToCareer',
            'attachToSemester',
            'attachToExceptionStudents',
            'attachToApplicantTag',
            'detachFromCareer',
            'detachFromSemester',
            'detachFromUsers',
            'detachFromExceptionStudents',
            'detachFromApplicantTag',
            'finalize',
            'disable',
            'activate',
            'cleanDeletedConcepts',
            'finalizePaymentConcepts'
        ];

        foreach ($methods as $method) {
            $this->assertMethodExists($method);
        }
    }

    #[Test]
    public function it_can_create_payment_concept(): void
    {
        $concept = new PaymentConcept(
            concept_name: 'Matrícula Semestral',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '5000.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
            description: 'Pago de matrícula para el semestre actual'
        );

        $result = $this->repository->create($concept);

        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertEquals('Matrícula Semestral', $result->concept_name);
        $this->assertEquals(PaymentConceptStatus::ACTIVO, $result->status);
        $this->assertNotNull($result->id);
        $this->assertTrue($result->isActive());
    }

    #[Test]
    public function it_can_update_payment_concept(): void
    {
        // Primero crear un concepto
        $concept = new PaymentConcept(
            concept_name: 'Concepto Original',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '1000.00',
            applies_to: PaymentConceptAppliesTo::TODOS
        );
        $created = $this->repository->create($concept);

        // Actualizar el concepto
        $data = [
            'concept_name' => 'Concepto Actualizado',
            'amount' => '1500.00',
            'description' => 'Descripción actualizada'
        ];

        $result = $this->repository->update($created->id, $data);

        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertEquals($created->id, $result->id);
        $this->assertEquals('Concepto Actualizado', $result->concept_name);
        $this->assertEquals('1500.00', $result->amount);
        $this->assertEquals('Descripción actualizada', $result->description);
    }

    #[Test]
    public function update_throws_exception_when_concept_not_found(): void
    {
        $conceptId = 999;
        $data = ['concept_name' => 'No existe'];

        $this->expectException(ModelNotFoundException::class);

        $this->repository->update($conceptId, $data);
    }

    #[Test]
    public function it_can_delete_logical(): void
    {
        // Crear un concepto
        $concept = new PaymentConcept(
            concept_name: 'Concepto a eliminar',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '2000.00',
            applies_to: PaymentConceptAppliesTo::TODOS
        );
        $created = $this->repository->create($concept);

        // Eliminar lógicamente
        $result = $this->repository->deleteLogical($created);

        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertTrue($result->isDelete());
        $this->assertEquals(PaymentConceptStatus::ELIMINADO, $result->status);
    }

    #[Test]
    public function it_can_delete_physical(): void
    {
        // Crear un concepto
        $concept = new PaymentConcept(
            concept_name: 'Concepto físico',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '3000.00',
            applies_to: PaymentConceptAppliesTo::TODOS
        );
        $created = $this->repository->create($concept);

        // Eliminar físicamente
        $this->repository->delete($created->id);

        // Verificar que ya no existe
        $stub = $this->repository;
        $this->expectException(ModelNotFoundException::class);

        $stub->update($created->id, ['concept_name' => 'Intentar actualizar']);
    }

    #[Test]
    public function it_can_attach_to_users(): void
    {
        // Crear un concepto
        $concept = new PaymentConcept(
            concept_name: 'Concepto con usuarios',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '1000.00',
            applies_to: PaymentConceptAppliesTo::ESTUDIANTES
        );
        $created = $this->repository->create($concept);

        // Adjuntar usuarios
        $userIds = new UserIdListDTO([1, 2, 3]);
        $result = $this->repository->attachToUsers($created->id, $userIds, false);

        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(3, $result->getUserIds());
        $this->assertTrue($result->hasUser(1));
        $this->assertTrue($result->hasUser(2));
        $this->assertTrue($result->hasUser(3));
    }

    #[Test]
    public function attach_to_users_can_replace_relations(): void
    {
        $stub = new PaymentConceptRepStub();

        // Crear un concepto
        $concept = new PaymentConcept(
            concept_name: 'Concepto reemplazo',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '1000.00',
            applies_to: PaymentConceptAppliesTo::ESTUDIANTES
        );
        $created = $stub->create($concept);

        // Primero adjuntar algunos usuarios
        $userIds1 = new UserIdListDTO([1, 2]);
        $result1 = $stub->attachToUsers($created->id, $userIds1, false);
        $this->assertCount(2, $result1->getUserIds());

        // Reemplazar con nuevos usuarios
        $userIds2 = new UserIdListDTO([3, 4, 5]);
        $result2 = $stub->attachToUsers($created->id, $userIds2, true);

        $this->assertCount(3, $result2->getUserIds());
        $this->assertFalse($result2->hasUser(1)); // Ya no debería tener usuario 1
        $this->assertTrue($result2->hasUser(3)); // Debería tener usuario 3
    }

    #[Test]
    public function it_can_attach_to_careers(): void
    {
        // Crear un concepto
        $concept = new PaymentConcept(
            concept_name: 'Concepto por carrera',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '1500.00',
            applies_to: PaymentConceptAppliesTo::CARRERA
        );
        $created = $this->repository->create($concept);

        // Adjuntar carreras
        $careerIds = [101, 102];
        $result = $this->repository->attachToCareer($created->id, $careerIds, false);

        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(2, $result->getCareerIds());
        $this->assertTrue($result->hasCareer(101));
        $this->assertTrue($result->hasCareer(102));
    }

    #[Test]
    public function it_can_attach_to_semesters(): void
    {
        // Crear un concepto
        $concept = new PaymentConcept(
            concept_name: 'Concepto por semestre',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '1200.00',
            applies_to: PaymentConceptAppliesTo::SEMESTRE
        );
        $created = $this->repository->create($concept);

        // Adjuntar semestres
        $semesters = [1, 2, 3];
        $result = $this->repository->attachToSemester($created->id, $semesters, false);

        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(3, $result->getSemesters());
        $this->assertTrue($result->hasSemester(1));
        $this->assertTrue($result->hasSemester(2));
        $this->assertTrue($result->hasSemester(3));
    }

    #[Test]
    public function it_can_attach_to_exception_students(): void
    {
        // Crear un concepto
        $concept = new PaymentConcept(
            concept_name: 'Concepto con excepciones',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '800.00',
            applies_to: PaymentConceptAppliesTo::TODOS
        );
        $created = $this->repository->create($concept);

        // Adjuntar excepciones
        $userIds = new UserIdListDTO([10, 11]);
        $result = $this->repository->attachToExceptionStudents($created->id, $userIds, false);

        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(2, $result->getExceptionUsersIds());
        $this->assertTrue($result->hasExceptionForUser(10));
        $this->assertTrue($result->hasExceptionForUser(11));
    }

    #[Test]
    public function it_can_attach_to_applicant_tags(): void
    {
        // Crear un concepto
        $concept = new PaymentConcept(
            concept_name: 'Concepto por tipo de aspirante',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '900.00',
            applies_to: PaymentConceptAppliesTo::TAG
        );
        $created = $this->repository->create($concept);

        // Adjuntar tags
        $tags = [PaymentConceptApplicantType::APPLICANT->value, PaymentConceptApplicantType::NO_STUDENT_DETAILS->value];
        $result = $this->repository->attachToApplicantTag($created->id, $tags, false);
        dump($result->getApplicantTag());
        var_dump($result->getApplicantTag());
        $test = $result->hasTag(PaymentConceptApplicantType::APPLICANT);
        dump('Resultado de hasTag(APPLICANT):', $test);
        dump('Por qué? Porque compara:', PaymentConceptApplicantType::APPLICANT, 'con', $result->getApplicantTag());
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(2, $result->getApplicantTag());
    }

    #[Test]
    public function it_can_detach_from_careers(): void
    {
        $stub = new PaymentConceptRepStub();

        // Crear concepto con carreras
        $concept = new PaymentConcept(
            concept_name: 'Concepto para desvincular',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '1000.00',
            applies_to: PaymentConceptAppliesTo::CARRERA,
            careerIds: [1, 2, 3]
        );
        $created = $stub->create($concept);
        $this->assertCount(3, $created->getCareerIds());

        // Desvincular carreras
        $stub->detachFromCareer($created->id);

        // Verificar que ya no tiene carreras
        $updated = $stub->getConcept($created->id);
        $this->assertCount(0, $updated->getCareerIds());
    }

    #[Test]
    public function it_can_detach_from_semesters(): void
    {
        $stub = new PaymentConceptRepStub();

        // Crear concepto con semestres
        $concept = new PaymentConcept(
            concept_name: 'Concepto semestral',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '1000.00',
            applies_to: PaymentConceptAppliesTo::SEMESTRE,
            semesters: [1, 2]
        );
        $created = $stub->create($concept);
        $this->assertCount(2, $created->getSemesters());

        // Desvincular semestres
        $stub->detachFromSemester($created->id);

        // Verificar que ya no tiene semestres
        $updated = $stub->getConcept($created->id);
        $this->assertCount(0, $updated->getSemesters());
    }

    #[Test]
    public function it_can_finalize_concept(): void
    {
        // Crear un concepto activo
        $concept = new PaymentConcept(
            concept_name: 'Concepto a finalizar',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now()->subDays(10),
            amount: '5000.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
            end_date: Carbon::now()->subDays(1) // Ya expiró
        );
        $created = $this->repository->create($concept);

        // Finalizar el concepto
        $result = $this->repository->finalize($created);

        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertTrue($result->isFinalize());
        $this->assertEquals(PaymentConceptStatus::FINALIZADO, $result->status);
        $this->assertNotNull($result->end_date);
    }

    #[Test]
    public function it_can_activate_concept(): void
    {
        // Crear un concepto desactivado
        $concept = new PaymentConcept(
            concept_name: 'Concepto a activar',
            status: PaymentConceptStatus::DESACTIVADO,
            start_date: Carbon::now(),
            amount: '3000.00',
            applies_to: PaymentConceptAppliesTo::TODOS
        );
        $created = $this->repository->create($concept);

        // Activar el concepto
        $result = $this->repository->activate($created);

        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertTrue($result->isActive());
        $this->assertEquals(PaymentConceptStatus::ACTIVO, $result->status);
        $this->assertNull($result->end_date);
    }

    #[Test]
    public function it_can_disable_concept(): void
    {
        // Crear un concepto activo
        $concept = new PaymentConcept(
            concept_name: 'Concepto a desactivar',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '4000.00',
            applies_to: PaymentConceptAppliesTo::TODOS
        );
        $created = $this->repository->create($concept);

        // Desactivar el concepto
        $result = $this->repository->disable($created);

        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertTrue($result->isDisable());
        $this->assertEquals(PaymentConceptStatus::DESACTIVADO, $result->status);
    }

    #[Test]
    public function it_can_clean_deleted_concepts(): void
    {
        $stub = new PaymentConceptRepStub();

        // Agregar conceptos eliminados
        $oldDeleted = new PaymentConcept(
            concept_name: 'Concepto viejo eliminado',
            status: PaymentConceptStatus::ELIMINADO,
            start_date: Carbon::now()->subDays(40), // Más viejo de 30 días
            amount: '1000.00',
            applies_to: PaymentConceptAppliesTo::TODOS
        );
        $stub->addConcept($oldDeleted);

        $recentDeleted = new PaymentConcept(
            concept_name: 'Concepto reciente eliminado',
            status: PaymentConceptStatus::ELIMINADO,
            start_date: Carbon::now()->subDays(10), // Menos de 30 días
            amount: '2000.00',
            applies_to: PaymentConceptAppliesTo::TODOS
        );
        $stub->addConcept($recentDeleted);

        $countBefore = $stub->getConceptsCount();
        $cleaned = $stub->cleanDeletedConcepts();
        $countAfter = $stub->getConceptsCount();

        $this->assertIsInt($cleaned);
        $this->assertGreaterThan(0, $cleaned);
        $this->assertEquals($countBefore - $cleaned, $countAfter);
    }

    #[Test]
    public function it_can_finalize_expired_concepts(): void
    {
        $stub = new PaymentConceptRepStub();

        // Agregar concepto activo expirado
        $expiredConcept = new PaymentConcept(
            concept_name: 'Concepto expirado',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now()->subDays(20),
            amount: '1500.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
            end_date: Carbon::now()->subDays(1) // Ya expiró
        );
        $stub->addConcept($expiredConcept);

        // Agregar concepto activo no expirado
        $activeConcept = new PaymentConcept(
            concept_name: 'Concepto activo',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '2000.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
            end_date: Carbon::now()->addDays(30) // No expirado
        );
        $stub->addConcept($activeConcept);

        $result = $stub->finalizePaymentConcepts();

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));

        // Verificar que el concepto expirado fue finalizado
        foreach ($result as $item) {
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('old_status', $item);
            $this->assertArrayHasKey('new_status', $item);
        }
    }

    #[Test]
    public function concept_can_be_expired(): void
    {
        $expiredConcept = new PaymentConcept(
            concept_name: 'Concepto expirado',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now()->subDays(10),
            amount: '1000.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
            end_date: Carbon::now()->subDays(1) // Expirado
        );

        $this->assertTrue($expiredConcept->isExpired());
        $this->assertTrue($expiredConcept->hasStarted());
    }

    #[Test]
    public function concept_can_be_global(): void
    {
        $globalConcept = new PaymentConcept(
            concept_name: 'Concepto global',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '5000.00',
            applies_to: PaymentConceptAppliesTo::TODOS
        );

        $this->assertTrue($globalConcept->isGlobal());
        $this->assertFalse($globalConcept->isDelete());
        $this->assertFalse($globalConcept->isDisable());
        $this->assertFalse($globalConcept->isFinalize());
    }

    #[Test]
    public function it_handles_database_errors_gracefully(): void
    {
        $stub = new PaymentConceptRepStub();
        $stub->shouldThrowDatabaseError(true);

        $concept = new PaymentConcept(
            concept_name: 'Concepto con error',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '1000.00',
            applies_to: PaymentConceptAppliesTo::TODOS
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database error');

        $stub->create($concept);
    }
}
