<?php

namespace Tests\Unit\Infraestructure\Repositories\Command;

use App\Core\Application\DTO\Response\User\UserIdListDTO;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Infraestructure\Repositories\Command\Payments\EloquentPaymentConceptRepository;
use App\Core\Infraestructure\Mappers\PaymentConceptMapper;
use App\Models\PaymentConcept as EloquentPaymentConcept;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentPaymentConceptRepositoryTest extends TestCase
{
    use RefreshDatabase;
    private EloquentPaymentConceptRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentPaymentConceptRepository();
    }

    #[Test]
    public function create_payment_concept_successfully(): void
    {
        // Arrange
        $concept = new PaymentConcept(
            concept_name: 'Test Concept',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::now(),
            amount: '1500.50',
            applies_to: PaymentConceptAppliesTo::TODOS,
            description: 'Test Description'
        );

        // Act
        $result = $this->repository->create($concept);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertNotNull($result->id);
        $this->assertEquals('Test Concept', $result->concept_name);
        $this->assertEquals('Test Description', $result->description);
        $this->assertEquals('1500.50', $result->amount);
        $this->assertEquals(PaymentConceptStatus::ACTIVO, $result->status);
        $this->assertEquals(PaymentConceptAppliesTo::TODOS, $result->applies_to);

        $this->assertDatabaseHas('payment_concepts', [
            'concept_name' => 'Test Concept',
            'description' => 'Test Description',
            'amount' => '1500.50',
            'status' => PaymentConceptStatus::ACTIVO->value,
            'applies_to' => PaymentConceptAppliesTo::TODOS->value,
        ]);
    }

    #[Test]
    public function update_payment_concept_successfully(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $newData = [
            'concept_name' => 'Updated Concept',
            'description' => 'Updated Description',
            'amount' => '2500.75',
        ];

        // Act
        $result = $this->repository->update($concept->id, $newData);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertEquals($concept->id, $result->id);
        $this->assertEquals('Updated Concept', $result->concept_name);
        $this->assertEquals('Updated Description', $result->description);
        $this->assertEquals('2500.75', $result->amount);

        $this->assertDatabaseHas('payment_concepts', [
            'id' => $concept->id,
            'concept_name' => 'Updated Concept',
            'description' => 'Updated Description',
            'amount' => '2500.75',
        ]);
    }

    #[Test]
    public function update_payment_concept_throws_exception_when_not_found(): void
    {
        // Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $this->repository->update(999999, ['concept_name' => 'Test']);
    }

    #[Test]
    public function finalize_payment_concept_sets_status_and_end_date(): void
    {
        // Arrange
        $eloquentConcept = EloquentPaymentConcept::factory()->active()->create();
        $domainConcept = PaymentConceptMapper::toDomain($eloquentConcept);
        $now = Carbon::now();

        // Act
        $result = $this->repository->finalize($domainConcept);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertEquals(PaymentConceptStatus::FINALIZADO, $result->status);
        $this->assertNotNull($result->end_date);

        $this->assertDatabaseHas('payment_concepts', [
            'id' => $eloquentConcept->id,
            'status' => PaymentConceptStatus::FINALIZADO->value,
        ]);
    }

    #[Test]
    public function activate_payment_concept_sets_active_status_and_removes_end_date(): void
    {
        // Arrange
        $eloquentConcept = EloquentPaymentConcept::factory()
            ->state(['status' => PaymentConceptStatus::DESACTIVADO, 'end_date' => Carbon::now()->subDays(5)])
            ->create();
        $domainConcept = PaymentConceptMapper::toDomain($eloquentConcept);

        // Act
        $result = $this->repository->activate($domainConcept);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertEquals(PaymentConceptStatus::ACTIVO, $result->status);
        $this->assertNull($result->end_date);

        $this->assertDatabaseHas('payment_concepts', [
            'id' => $eloquentConcept->id,
            'status' => PaymentConceptStatus::ACTIVO->value,
            'end_date' => null,
        ]);
    }

    #[Test]
    public function disable_payment_concept_sets_inactive_status(): void
    {
        // Arrange
        $eloquentConcept = EloquentPaymentConcept::factory()->active()->create();
        $domainConcept = PaymentConceptMapper::toDomain($eloquentConcept);

        // Act
        $result = $this->repository->disable($domainConcept);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertEquals(PaymentConceptStatus::DESACTIVADO, $result->status);

        $this->assertDatabaseHas('payment_concepts', [
            'id' => $eloquentConcept->id,
            'status' => PaymentConceptStatus::DESACTIVADO->value,
        ]);
    }

    #[Test]
    public function delete_logical_sets_eliminado_status(): void
    {
        // Arrange
        $eloquentConcept = EloquentPaymentConcept::factory()->active()->create();
        $domainConcept = PaymentConceptMapper::toDomain($eloquentConcept);

        // Act
        $result = $this->repository->deleteLogical($domainConcept);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertEquals(PaymentConceptStatus::ELIMINADO, $result->status);

        $this->assertDatabaseHas('payment_concepts', [
            'id' => $eloquentConcept->id,
            'status' => PaymentConceptStatus::ELIMINADO->value,
        ]);
    }

    #[Test]
    public function delete_physical_removes_concept_from_database(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();

        // Act
        $this->repository->delete($concept->id);

        // Assert
        $this->assertDatabaseMissing('payment_concepts', [
            'id' => $concept->id,
        ]);
    }

    #[Test]
    public function delete_physical_throws_exception_when_not_found(): void
    {
        // Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $this->repository->delete(999999);
    }

    #[Test]
    public function attach_users_to_concept_without_replacement(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $users = \App\Models\User::factory()->count(3)->create();
        $userIds = new UserIdListDTO($users->pluck('id')->toArray());

        // Act
        $result = $this->repository->attachToUsers($concept->id, $userIds, false);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(3, $result->getUserIds());

        foreach ($users as $user) {
            $this->assertDatabaseHas('payment_concept_user', [
                'payment_concept_id' => $concept->id,
                'user_id' => $user->id,
            ]);
        }
    }

    #[Test]
    public function attach_users_to_concept_with_replacement(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $initialUsers = \App\Models\User::factory()->count(2)->create();
        $concept->users()->attach($initialUsers);

        $newUsers = \App\Models\User::factory()->count(3)->create();
        $userIds = new UserIdListDTO($newUsers->pluck('id')->toArray());

        // Act
        $result = $this->repository->attachToUsers($concept->id, $userIds, true);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(3, $result->getUserIds());

        // New users should be attached
        foreach ($newUsers as $user) {
            $this->assertDatabaseHas('payment_concept_user', [
                'payment_concept_id' => $concept->id,
                'user_id' => $user->id,
            ]);
        }

        // Old users should be detached
        foreach ($initialUsers as $user) {
            $this->assertDatabaseMissing('payment_concept_user', [
                'payment_concept_id' => $concept->id,
                'user_id' => $user->id,
            ]);
        }
    }

    #[Test]
    public function attach_careers_to_concept(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $careers = \App\Models\Career::factory()->count(2)->create();
        $careerIds = $careers->pluck('id')->toArray();

        // Act
        $result = $this->repository->attachToCareer($concept->id, $careerIds, false);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(2, $result->getCareerIds());

        foreach ($careers as $career) {
            $this->assertDatabaseHas('career_payment_concept', [
                'payment_concept_id' => $concept->id,
                'career_id' => $career->id,
            ]);
        }
    }

    #[Test]
    public function attach_semesters_to_concept(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $semesters = [1, 3, 5, 7];

        // Act
        $result = $this->repository->attachToSemester($concept->id, $semesters, false);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(4, $result->getSemesters());

        foreach ($semesters as $semester) {
            $this->assertDatabaseHas('payment_concept_semester', [
                'payment_concept_id' => $concept->id,
                'semestre' => $semester,
            ]);
        }
    }

    #[Test]
    public function attach_exception_students_to_concept(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $users = \App\Models\User::factory()->count(2)->create();
        $userIds = new UserIdListDTO($users->pluck('id')->toArray());

        // Act
        $result = $this->repository->attachToExceptionStudents($concept->id, $userIds, false);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(2, $result->getExceptionUsersIds());

        foreach ($users as $user) {
            $this->assertDatabaseHas('concept_exceptions', [
                'payment_concept_id' => $concept->id,
                'user_id' => $user->id,
            ]);
        }
    }

    #[Test]
    public function attach_applicant_tags_to_concept(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $tags = PaymentConceptApplicantType::cases();

        // Act
        $result = $this->repository->attachToApplicantTag($concept->id, $tags, false);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(2, $result->getApplicantTag());

        foreach ($tags as $tag) {
            $this->assertDatabaseHas('payment_concept_applicant_tags', [
                'payment_concept_id' => $concept->id,
                'tag' => $tag,
            ]);
        }
    }

    #[Test]
    public function attach_users_to_concept_without_replacement_adds_new_users(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $users = \App\Models\User::factory()->count(3)->create();
        $userIds = new UserIdListDTO($users->pluck('id')->toArray());

        // Act
        $result = $this->repository->attachToUsers($concept->id, $userIds, false);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(3, $result->getUserIds());

        foreach ($users as $user) {
            $this->assertDatabaseHas('payment_concept_user', [
                'payment_concept_id' => $concept->id,
                'user_id' => $user->id,
            ]);
        }
    }

    #[Test]
    public function attach_users_to_concept_without_replacement_ignores_existing_users(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $users = \App\Models\User::factory()->count(3)->create();

        // Attach primeros 2 usuarios
        $concept->users()->attach([$users[0]->id, $users[1]->id]);

        // Intentar attach todos los usuarios (2 ya existen)
        $userIds = new UserIdListDTO($users->pluck('id')->toArray());

        // Act
        $result = $this->repository->attachToUsers($concept->id, $userIds, false);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(3, $result->getUserIds()); // Todos los usuarios

        // Verificar que no hay duplicados
        $relationCount = DB::table('payment_concept_user')
            ->where('payment_concept_id', $concept->id)
            ->count();
        $this->assertEquals(3, $relationCount);
    }

    #[Test]
    public function attach_users_to_concept_without_replacement_preserves_existing_relations(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $existingUsers = \App\Models\User::factory()->count(2)->create();
        $newUsers = \App\Models\User::factory()->count(2)->create();

        // Attach usuarios existentes
        $concept->users()->attach($existingUsers);

        // Attach solo nuevos usuarios (sin replacement)
        $userIds = new UserIdListDTO($newUsers->pluck('id')->toArray());

        // Act
        $result = $this->repository->attachToUsers($concept->id, $userIds, false);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(4, $result->getUserIds()); // 2 existentes + 2 nuevos

        // Todos deben estar presentes
        foreach ($existingUsers as $user) {
            $this->assertContains($user->id, $result->getUserIds());
        }
        foreach ($newUsers as $user) {
            $this->assertContains($user->id, $result->getUserIds());
        }
    }

    #[Test]
    public function attach_users_to_concept_with_replacement_replaces_all_relations(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $initialUsers = \App\Models\User::factory()->count(2)->create();
        $concept->users()->attach($initialUsers);

        $newUsers = \App\Models\User::factory()->count(3)->create();
        $userIds = new UserIdListDTO($newUsers->pluck('id')->toArray());

        // Act
        $result = $this->repository->attachToUsers($concept->id, $userIds, true);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(3, $result->getUserIds());

        // New users should be attached
        foreach ($newUsers as $user) {
            $this->assertDatabaseHas('payment_concept_user', [
                'payment_concept_id' => $concept->id,
                'user_id' => $user->id,
            ]);
        }

        // Old users should be detached
        foreach ($initialUsers as $user) {
            $this->assertDatabaseMissing('payment_concept_user', [
                'payment_concept_id' => $concept->id,
                'user_id' => $user->id,
            ]);
        }
    }

    #[Test]
    public function attach_users_to_concept_with_replacement_and_empty_array_removes_all(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $users = \App\Models\User::factory()->count(2)->create();
        $concept->users()->attach($users);

        $emptyUserIds = new UserIdListDTO([]);

        // Act
        $result = $this->repository->attachToUsers($concept->id, $emptyUserIds, true);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(0, $result->getUserIds());

        $this->assertDatabaseMissing('payment_concept_user', [
            'payment_concept_id' => $concept->id,
        ]);
    }

    #[Test]
    public function attach_users_to_nonexistent_concept_throws_exception(): void
    {
        // Arrange
        $users = \App\Models\User::factory()->count(2)->create();
        $userIds = new UserIdListDTO($users->pluck('id')->toArray());

        // Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $this->repository->attachToUsers(999999, $userIds, false);
    }

    // ==================== ATTACH CAREERS ====================

    #[Test]
    public function attach_careers_to_concept_without_replacement(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $careers = \App\Models\Career::factory()->count(2)->create();
        $careerIds = $careers->pluck('id')->toArray();

        // Act
        $result = $this->repository->attachToCareer($concept->id, $careerIds, false);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(2, $result->getCareerIds());

        foreach ($careers as $career) {
            $this->assertDatabaseHas('career_payment_concept', [
                'payment_concept_id' => $concept->id,
                'career_id' => $career->id,
            ]);
        }
    }

    #[Test]
    public function attach_careers_to_concept_with_replacement(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $initialCareers = \App\Models\Career::factory()->count(2)->create();
        $concept->careers()->attach($initialCareers);

        $newCareers = \App\Models\Career::factory()->count(3)->create();
        $newCareerIds = $newCareers->pluck('id')->toArray();

        // Act
        $result = $this->repository->attachToCareer($concept->id, $newCareerIds, true);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(3, $result->getCareerIds());

        foreach ($newCareers as $career) {
            $this->assertDatabaseHas('career_payment_concept', [
                'payment_concept_id' => $concept->id,
                'career_id' => $career->id,
            ]);
        }

        foreach ($initialCareers as $career) {
            $this->assertDatabaseMissing('career_payment_concept', [
                'payment_concept_id' => $concept->id,
                'career_id' => $career->id,
            ]);
        }
    }

    #[Test]
    public function attach_careers_without_replacement_ignores_existing_careers(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $careers = \App\Models\Career::factory()->count(3)->create();

        // Attach primeros 2 carreras
        $concept->careers()->attach([$careers[0]->id, $careers[1]->id]);

        // Intentar attach todas las carreras
        $careerIds = $careers->pluck('id')->toArray();

        // Act
        $result = $this->repository->attachToCareer($concept->id, $careerIds, false);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(3, $result->getCareerIds());

        // Verificar que no hay duplicados
        $relationCount = DB::table('career_payment_concept')
            ->where('payment_concept_id', $concept->id)
            ->count();
        $this->assertEquals(3, $relationCount);
    }

    // ==================== ATTACH SEMESTERS ====================

    #[Test]
    public function attach_semesters_to_concept_without_replacement(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $semesters = [1, 3, 5, 7];

        // Act
        $result = $this->repository->attachToSemester($concept->id, $semesters, false);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(4, $result->getSemesters());

        foreach ($semesters as $semester) {
            $this->assertDatabaseHas('payment_concept_semester', [
                'payment_concept_id' => $concept->id,
                'semestre' => $semester,
            ]);
        }
    }

    #[Test]
    public function attach_semesters_to_concept_with_replacement(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();

        // Semestres iniciales
        $concept->paymentConceptSemesters()->create(['semestre' => 1]);
        $concept->paymentConceptSemesters()->create(['semestre' => 2]);

        $newSemesters = [3, 4, 5];

        // Act
        $result = $this->repository->attachToSemester($concept->id, $newSemesters, true);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(3, $result->getSemesters());

        foreach ($newSemesters as $semester) {
            $this->assertDatabaseHas('payment_concept_semester', [
                'payment_concept_id' => $concept->id,
                'semestre' => $semester,
            ]);
        }

        // Old semesters should be removed
        $this->assertDatabaseMissing('payment_concept_semester', [
            'payment_concept_id' => $concept->id,
            'semestre' => 1,
        ]);
        $this->assertDatabaseMissing('payment_concept_semester', [
            'payment_concept_id' => $concept->id,
            'semestre' => 2,
        ]);
    }

    #[Test]
    public function attach_semesters_without_replacement_updates_existing_records(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();

        // Crear semestre existente con old timestamp
        $existingSemester = $concept->paymentConceptSemesters()->create([
            'semestre' => 1,
            'created_at' => Carbon::now()->subDay(),
            'updated_at' => Carbon::now()->subDay(),
        ]);

        $oldUpdatedAt = clone $existingSemester->updated_at;
        sleep(1);
        $newSemesters = [1, 2]; // 1 ya existe, 2 es nuevo

        // Act
        $result = $this->repository->attachToSemester($concept->id, $newSemesters, false);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(2, $result->getSemesters());

        // Verificar que el semestre 1 se actualizó (nuevo updated_at)
        $existingSemester->refresh();
        $this->assertNotEquals($oldUpdatedAt, $existingSemester->updated_at);
        $this->assertTrue($existingSemester->updated_at->greaterThan($oldUpdatedAt));

        // Verificar que no hay duplicados
        $semester1Count = DB::table('payment_concept_semester')
            ->where('payment_concept_id', $concept->id)
            ->where('semestre', 1)
            ->count();
        $this->assertEquals(1, $semester1Count);
    }

    #[Test]
    public function attach_semesters_with_replacement_and_empty_array_removes_all(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $concept->paymentConceptSemesters()->create(['semestre' => 1]);
        $concept->paymentConceptSemesters()->create(['semestre' => 2]);

        // Act
        $result = $this->repository->attachToSemester($concept->id, [], true);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(0, $result->getSemesters());

        $this->assertDatabaseMissing('payment_concept_semester', [
            'payment_concept_id' => $concept->id,
        ]);
    }

    #[Test]
    public function attach_duplicate_semesters_does_not_create_duplicates(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $semesters = [1, 1, 2, 2, 3]; // Duplicados intencionales

        // Act
        $result = $this->repository->attachToSemester($concept->id, $semesters, false);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        // Solo debería haber 3 semestres únicos
        $this->assertCount(3, $result->getSemesters());

        $semesterCount = DB::table('payment_concept_semester')
            ->where('payment_concept_id', $concept->id)
            ->count();
        $this->assertEquals(3, $semesterCount);
    }

    // ==================== ATTACH EXCEPTION STUDENTS ====================

    #[Test]
    public function attach_exception_students_to_concept_without_replacement(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $users = \App\Models\User::factory()->count(2)->create();
        $userIds = new UserIdListDTO($users->pluck('id')->toArray());

        // Act
        $result = $this->repository->attachToExceptionStudents($concept->id, $userIds, false);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(2, $result->getExceptionUsersIds());

        foreach ($users as $user) {
            $this->assertDatabaseHas('concept_exceptions', [
                'payment_concept_id' => $concept->id,
                'user_id' => $user->id,
            ]);
        }
    }

    #[Test]
    public function attach_exception_students_to_concept_with_replacement(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $initialUsers = \App\Models\User::factory()->count(2)->create();
        $concept->exceptions()->attach($initialUsers);

        $newUsers = \App\Models\User::factory()->count(3)->create();
        $userIds = new UserIdListDTO($newUsers->pluck('id')->toArray());

        // Act
        $result = $this->repository->attachToExceptionStudents($concept->id, $userIds, true);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(3, $result->getExceptionUsersIds());

        foreach ($newUsers as $user) {
            $this->assertDatabaseHas('concept_exceptions', [
                'payment_concept_id' => $concept->id,
                'user_id' => $user->id,
            ]);
        }

        foreach ($initialUsers as $user) {
            $this->assertDatabaseMissing('concept_exceptions', [
                'payment_concept_id' => $concept->id,
                'user_id' => $user->id,
            ]);
        }
    }

    #[Test]
    public function attach_exception_students_without_replacement_ignores_existing(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $users = \App\Models\User::factory()->count(3)->create();

        // Attach primeros 2 usuarios como excepciones
        $concept->exceptions()->attach([$users[0]->id, $users[1]->id]);

        // Intentar attach todos los usuarios como excepciones
        $userIds = new UserIdListDTO($users->pluck('id')->toArray());

        // Act
        $result = $this->repository->attachToExceptionStudents($concept->id, $userIds, false);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(3, $result->getExceptionUsersIds());

        $relationCount = DB::table('concept_exceptions')
            ->where('payment_concept_id', $concept->id)
            ->count();
        $this->assertEquals(3, $relationCount);
    }

    // ==================== ATTACH APPLICANT TAGS ====================

    #[Test]
    public function attach_applicant_tags_to_concept_without_replacement(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();

        // Obtener valores válidos del enum
        $enumCases = PaymentConceptApplicantType::cases();
        $tags = array_map(fn($case) => $case->value, $enumCases);

        // Act
        $result = $this->repository->attachToApplicantTag($concept->id, $tags, false);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(count($tags), $result->getApplicantTag());

        foreach ($tags as $tag) {
            $this->assertDatabaseHas('payment_concept_applicant_tags', [
                'payment_concept_id' => $concept->id,
                'tag' => $tag,
            ]);
        }
    }

    #[Test]
    public function attach_applicant_tags_to_concept_with_replacement(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();

        // Tags iniciales
        $initialTags = [
            PaymentConceptApplicantType::APPLICANT->value,
        ];
        foreach ($initialTags as $tag) {
            $concept->applicantTypes()->create(['tag' => $tag]);
        }

        // Nuevos tags
        $newTags = [
            PaymentConceptApplicantType::NO_STUDENT_DETAILS->value,
        ];

        // Act
        $result = $this->repository->attachToApplicantTag($concept->id, $newTags, true);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(1, $result->getApplicantTag());

        foreach ($newTags as $tag) {
            $this->assertDatabaseHas('payment_concept_applicant_tags', [
                'payment_concept_id' => $concept->id,
                'tag' => $tag,
            ]);
        }

        foreach ($initialTags as $tag) {
            $this->assertDatabaseMissing('payment_concept_applicant_tags', [
                'payment_concept_id' => $concept->id,
                'tag' => $tag,
            ]);
        }
    }

    #[Test]
    public function attach_applicant_tags_without_replacement_updates_existing(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();

        // Crear tag existente
        $existingTag = PaymentConceptApplicantType::NO_STUDENT_DETAILS->value;
        $concept->applicantTypes()->create([
            'tag' => $existingTag,
            'created_at' => Carbon::now()->subDay(),
            'updated_at' => Carbon::now()->subDay(),
        ]);

        // Nuevos tags (incluye uno existente)
        $tags = [
            $existingTag,
            PaymentConceptApplicantType::APPLICANT->value,
        ];

        // Act
        $result = $this->repository->attachToApplicantTag($concept->id, $tags, false);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(2, $result->getApplicantTag());

        // Verificar que no hay duplicados
        $tagCount = DB::table('payment_concept_applicant_tags')
            ->where('payment_concept_id', $concept->id)
            ->where('tag', $existingTag)
            ->count();
        $this->assertEquals(1, $tagCount);
    }

    #[Test]
    public function attach_applicant_tags_with_replacement_and_empty_array_removes_all(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();

        $tags = [
            PaymentConceptApplicantType::NO_STUDENT_DETAILS->value,
            PaymentConceptApplicantType::APPLICANT->value,
        ];
        foreach ($tags as $tag) {
            $concept->applicantTypes()->create(['tag' => $tag]);
        }

        // Act
        $result = $this->repository->attachToApplicantTag($concept->id, [], true);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertCount(0, $result->getApplicantTag());

        $this->assertDatabaseMissing('payment_concept_applicant_tags', [
            'payment_concept_id' => $concept->id,
        ]);
    }

    // ==================== DETACH METHODS ====================

    #[Test]
    public function detach_from_exception_students_removes_all_exceptions(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $users = \App\Models\User::factory()->count(2)->create();
        $concept->exceptions()->attach($users);

        // Act
        $this->repository->detachFromExceptionStudents($concept->id);

        // Assert
        $this->assertDatabaseMissing('concept_exceptions', [
            'payment_concept_id' => $concept->id,
        ]);
    }

    #[Test]
    public function detach_from_exception_students_when_none_exist_does_nothing(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();

        // Act & Assert - No exception should be thrown
        $this->repository->detachFromExceptionStudents($concept->id);

        $this->assertDatabaseMissing('concept_exceptions', [
            'payment_concept_id' => $concept->id,
        ]);
    }

    #[Test]
    public function detach_from_semesters_removes_all_semesters(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $concept->paymentConceptSemesters()->create(['semestre' => 1]);
        $concept->paymentConceptSemesters()->create(['semestre' => 2]);

        // Act
        $this->repository->detachFromSemester($concept->id);

        // Assert
        $this->assertDatabaseMissing('payment_concept_semester', [
            'payment_concept_id' => $concept->id,
        ]);
    }

    #[Test]
    public function detach_from_careers_removes_all_career_relations(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $career = \App\Models\Career::factory()->create();
        $concept->careers()->attach($career);

        // Act
        $this->repository->detachFromCareer($concept->id);

        // Assert
        $this->assertDatabaseMissing('career_payment_concept', [
            'payment_concept_id' => $concept->id,
        ]);
    }

    #[Test]
    public function detach_from_users_removes_all_user_relations(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $user = \App\Models\User::factory()->create();
        $concept->users()->attach($user);

        // Act
        $this->repository->detachFromUsers($concept->id);

        // Assert
        $this->assertDatabaseMissing('payment_concept_user', [
            'payment_concept_id' => $concept->id,
        ]);
    }

    #[Test]
    public function detach_from_applicant_tags_removes_all_tags(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $concept->applicantTypes()->create([
            'tag' => PaymentConceptApplicantType::NO_STUDENT_DETAILS->value
        ]);

        // Act
        $this->repository->detachFromApplicantTag($concept->id);

        // Assert
        $this->assertDatabaseMissing('payment_concept_applicant_tags', [
            'payment_concept_id' => $concept->id,
        ]);
    }

    #[Test]
    public function detach_from_nonexistent_concept_throws_exception(): void
    {
        // Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $this->repository->detachFromUsers(999999);
    }

    // ==================== COMPREHENSIVE TESTS ====================

    #[Test]
    public function multiple_attach_and_detach_operations_work_correctly(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $users = \App\Models\User::factory()->count(5)->create();
        $careers = \App\Models\Career::factory()->count(3)->create();

        // Act 1: Attach primeros 3 usuarios
        $userIds1 = new UserIdListDTO([$users[0]->id, $users[1]->id, $users[2]->id]);
        $result1 = $this->repository->attachToUsers($concept->id, $userIds1, false);
        $this->assertCount(3, $result1->getUserIds());

        // Act 2: Attach otros 2 usuarios (sin replacement)
        $userIds2 = new UserIdListDTO([$users[2]->id, $users[3]->id, $users[4]->id]);
        $result2 = $this->repository->attachToUsers($concept->id, $userIds2, false);
        $this->assertCount(5, $result2->getUserIds()); // Todos los usuarios

        // Act 3: Attach SOLO 2 carreras (no las 3)
        $careerIds = [$careers[0]->id, $careers[1]->id]; // Solo carreras 0 y 1
        $result3 = $this->repository->attachToCareer($concept->id, $careerIds, false);
        $this->assertCount(2, $result3->getCareerIds());

        // Act 4: Detach todos los usuarios
        $this->repository->detachFromUsers($concept->id);

        // Assert
        $this->assertDatabaseMissing('payment_concept_user', [
            'payment_concept_id' => $concept->id,
        ]);

        // Solo las 2 carreras que se attacharon deben seguir existiendo
        foreach ([$careers[0], $careers[1]] as $career) {
            $this->assertDatabaseHas('career_payment_concept', [
                'payment_concept_id' => $concept->id,
                'career_id' => $career->id,
            ]);
        }

        // La carrera 2 NO debe existir porque nunca se attachó
        $this->assertDatabaseMissing('career_payment_concept', [
            'payment_concept_id' => $concept->id,
            'career_id' => $careers[2]->id,
        ]);
    }

    #[Test]
    public function domain_concept_contains_correct_relation_ids_after_attach(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $users = \App\Models\User::factory()->count(3)->create();
        $careers = \App\Models\Career::factory()->count(2)->create();

        $userIds = new UserIdListDTO($users->pluck('id')->toArray());
        $careerIds = $careers->pluck('id')->toArray();

        // Act
        $userResult = $this->repository->attachToUsers($concept->id, $userIds, false);
        $careerResult = $this->repository->attachToCareer($concept->id, $careerIds, false);

        // Assert
        $this->assertEquals($users->pluck('id')->sort()->values()->all(),
            collect($userResult->getUserIds())->sort()->values()->all());

        $this->assertEquals($careers->pluck('id')->sort()->values()->all(),
            collect($careerResult->getCareerIds())->sort()->values()->all());
    }

    #[Test]
    public function clean_deleted_concepts_removes_old_eliminado_concepts(): void
    {
        // Arrange
        // Concept deleted recently (should NOT be removed)
        $recentDeleted = EloquentPaymentConcept::factory()->state([
            'status' => PaymentConceptStatus::ELIMINADO,
            'mark_as_deleted_at' => now()->subDays(2)
        ])->create();

        // Concepts deleted more than 30 days ago (should be removed)
        $oldDeleted = EloquentPaymentConcept::factory()->state([
            'status' => PaymentConceptStatus::ELIMINADO,
            'mark_as_deleted_at' => now()->subDays(32)
        ])->count(3)->create();

        // Active concept (should NOT be removed)
        $activeConcept = EloquentPaymentConcept::factory()->active()->create();

        // Act
        $deletedCount = $this->repository->cleanDeletedConcepts();

        // Assert
        $this->assertEquals(3, $deletedCount);

        // Recent deleted should still exist
        $this->assertDatabaseHas('payment_concepts', ['id' => $recentDeleted->id]);

        // Active concept should still exist
        $this->assertDatabaseHas('payment_concepts', ['id' => $activeConcept->id]);

        // Old deleted should be removed
        foreach ($oldDeleted as $concept) {
            $this->assertDatabaseMissing('payment_concepts', ['id' => $concept->id]);
        }
    }

    #[Test]
    public function finalize_payment_concepts_automatically_finalizes_expired_active_concepts(): void
    {
        // Arrange
        // Active concepts with past end date (should be finalized)
        $expiredActive = EloquentPaymentConcept::factory()
            ->active()
            ->state(['end_date' => Carbon::now()->subDays(5)])
            ->count(2)
            ->create();

        // Active concepts with future end date (should NOT be finalized)
        $notExpiredActive = EloquentPaymentConcept::factory()
            ->active()
            ->state(['end_date' => Carbon::now()->addDays(5)])
            ->count(2)
            ->create();

        // Inactive concepts with past end date (should NOT be finalized)
        $expiredInactive = EloquentPaymentConcept::factory()
            ->state(['status' => PaymentConceptStatus::DESACTIVADO, 'end_date' => Carbon::now()->subDays(5)])
            ->create();

        // Active concepts without end date (should NOT be finalized)
        $noEndDateActive = EloquentPaymentConcept::factory()
            ->active()
            ->state(['end_date' => null])
            ->create();

        // Act
        $result = $this->repository->finalizePaymentConcepts();

        // Assert
        $this->assertCount(2, $result);

        foreach ($expiredActive as $concept) {
            $this->assertDatabaseHas('payment_concepts', [
                'id' => $concept->id,
                'status' => PaymentConceptStatus::FINALIZADO->value,
            ]);
        }

        foreach ($notExpiredActive as $concept) {
            $this->assertDatabaseHas('payment_concepts', [
                'id' => $concept->id,
                'status' => PaymentConceptStatus::ACTIVO->value,
            ]);
        }

        $this->assertDatabaseHas('payment_concepts', [
            'id' => $expiredInactive->id,
            'status' => PaymentConceptStatus::DESACTIVADO->value,
        ]);

        $this->assertDatabaseHas('payment_concepts', [
            'id' => $noEndDateActive->id,
            'status' => PaymentConceptStatus::ACTIVO->value,
        ]);
    }

    #[Test]
    public function find_or_fail_loads_all_relations(): void
    {
        // Arrange
        $concept = EloquentPaymentConcept::factory()->create();
        $user = \App\Models\User::factory()->create();
        $career = \App\Models\Career::factory()->create();

        $concept->users()->attach($user);
        $concept->careers()->attach($career);
        $concept->paymentConceptSemesters()->create(['semestre' => 1]);
        $concept->exceptions()->attach($user);
        $concept->applicantTypes()->create(['tag' => PaymentConceptApplicantType::APPLICANT]);

        // Act - Use update method which calls findOrFail internally
        $result = $this->repository->update($concept->id, []);

        // Assert
        $this->assertInstanceOf(PaymentConcept::class, $result);
        $this->assertNotEmpty($result->getUserIds());
        $this->assertNotEmpty($result->getCareerIds());
        $this->assertNotEmpty($result->getSemesters());
        $this->assertNotEmpty($result->getExceptionUsersIds());
        $this->assertNotEmpty($result->getApplicantTag());
    }

}
