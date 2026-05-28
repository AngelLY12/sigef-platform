<?php

namespace Tests\Unit\Infraestructure\Repositories\Command;

use App\Core\Application\DTO\Request\StudentDetail\CreateStudentDetailDTO;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Infraestructure\Repositories\Command\User\EloquentStudentDetailRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Core\Domain\Entities\User as DomainUser;
use App\Core\Domain\Entities\StudentDetail;
use App\Models\StudentDetail as EloquentStudentDetail;
use App\Models\User as ModelsUser;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EloquentStudentDetailRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentStudentDetailRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => UserRoles::STUDENT->value, 'guard_name' => 'web']);
        $this->repository = new EloquentStudentDetailRepository();
    }

    // ==================== FIND STUDENT DETAILS TESTS ====================

    #[Test]
    public function find_student_details_returns_domain_object_when_exists(): void
    {
        // Arrange
        $user = ModelsUser::factory()->create();
        $studentDetail = EloquentStudentDetail::factory()->forUser($user)->create();

        // Act
        $result = $this->repository->findStudentDetails($user->id);

        // Assert
        $this->assertInstanceOf(StudentDetail::class, $result);
        $this->assertEquals($user->id, $result->user_id);
        $this->assertEquals($studentDetail->career_id, $result->career_id);
        $this->assertEquals($studentDetail->n_control, $result->n_control);
        $this->assertEquals($studentDetail->semestre, $result->semestre);
        $this->assertEquals($studentDetail->group, $result->group);
        $this->assertEquals($studentDetail->workshop, $result->workshop);
    }

    #[Test]
    public function find_student_details_returns_null_when_not_exists(): void
    {
        // Arrange
        $user = ModelsUser::factory()->create();

        // Act
        $result = $this->repository->findStudentDetails($user->id);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function find_student_details_returns_null_for_nonexistent_user(): void
    {
        // Act
        $result = $this->repository->findStudentDetails(999999);

        // Assert
        $this->assertNull($result);
    }

    // ==================== INSERT STUDENT DETAILS TESTS ====================

    #[Test]
    public function insert_student_details_inserts_multiple_records(): void
    {
        // Arrange
        $users = ModelsUser::factory()->count(3)->create();

        $studentDetails = [];
        $controlNumber = 20230001;

        foreach ($users as $user) {
            $studentDetails[] = [
                'user_id' => $user->id,
                'career_id' => \App\Models\Career::factory()->create()->id,
                'n_control' => $controlNumber++,
                'semestre' => rand(1, 10),
                'group' => 'A',
                'workshop' => 'Taller de Matemáticas',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Act
        $insertedCount = $this->repository->insertStudentDetails($studentDetails);
        echo "Total count : " . $insertedCount . PHP_EOL;
        // Assert
        $this->assertEquals(3, $insertedCount);

        foreach ($studentDetails as $detail) {
            $this->assertDatabaseHas('student_details', [
                'user_id' => $detail['user_id'],
                'n_control' => $detail['n_control'],
            ]);
        }
    }

    #[Test]
    public function insert_student_details_returns_zero_for_empty_array(): void
    {
        // Arrange
        $emptyArray = [];

        // Act
        $result = $this->repository->insertStudentDetails($emptyArray);

        // Assert
        $this->assertEquals(0, $result);
    }

    #[Test]
    public function insert_single_student_detail_successfully(): void
    {
        // Arrange
        $user = ModelsUser::factory()->create();
        $career = \App\Models\Career::factory()->create();

        $detail = [
            'user_id' => $user->id,
            'career_id' => $career->id,
            'n_control' => 20230001,
            'semestre' => 5,
            'group' => 'B',
            'workshop' => 'Taller de Programación',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Act
        $result = $this->repository->insertSingleStudentDetail($detail);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('student_details', [
            'user_id' => $user->id,
            'n_control' => 20230001,
        ]);
    }

    #[Test]
    public function insert_single_student_detail_throws_exception_on_error(): void
    {
        // Arrange
        // Intentar insertar sin user_id requerido
        $detail = [
            'career_id' => null,
            'semestre' => 5,
        ];

        // Assert
        $this->expectException(\Exception::class);

        // Act
        $this->repository->insertSingleStudentDetail($detail);
    }

    // ==================== INCREMENT SEMESTER TESTS ====================

    #[Test]
    public function increment_semester_for_all_active_students(): void
    {
        // Arrange
        $activeUser1 = ModelsUser::factory()->state(['status' => UserStatus::ACTIVO])->create();
        $activeUser2 = ModelsUser::factory()->state(['status' => UserStatus::ACTIVO])->create();
        $temporalUser = ModelsUser::factory()->state(['status' => UserStatus::BAJA_TEMPORAL])->create();
        $inactiveUser = ModelsUser::factory()->state(['status' => UserStatus::BAJA])->create();

        // Crear detalles de estudiante
        $detail1 = EloquentStudentDetail::factory()->forUser($activeUser1)->create(['semestre' => 5]);
        $detail2 = EloquentStudentDetail::factory()->forUser($activeUser2)->create(['semestre' => 8]);
        $detail3 = EloquentStudentDetail::factory()->forUser($temporalUser)->create(['semestre' => 3]);
        $detail4 = EloquentStudentDetail::factory()->forUser($inactiveUser)->create(['semestre' => 6]);

        // Act
        $incrementedCount = $this->repository->incrementSemesterForAll();

        // Assert
        $this->assertEquals(3, $incrementedCount); // Solo usuarios activos y baja temporal

        // Verificar incrementos
        $detail1->refresh();
        $detail2->refresh();
        $detail3->refresh();
        $detail4->refresh();

        $this->assertEquals(6, $detail1->semestre); // 5 -> 6
        $this->assertEquals(9, $detail2->semestre); // 8 -> 9
        $this->assertEquals(4, $detail3->semestre); // 3 -> 4
        $this->assertEquals(6, $detail4->semestre); // No cambió (inactivo)
    }

    #[Test]
    public function increment_semester_only_up_to_limit(): void
    {
        // Arrange
        // Crear estudiantes en diferentes semestres, algunos cerca del límite
        $users = ModelsUser::factory()->count(5)->state(['status' => UserStatus::ACTIVO])->create();

        $details = [
            ['semestre' => 9, 'expected' => 10],
            ['semestre' => 10, 'expected' => 10], // No incrementa (ya en límite)
            ['semestre' => 11, 'expected' => 11], // No incrementa (ya pasado límite)
            ['semestre' => 5, 'expected' => 6],   // Incrementa normalmente
            ['semestre' => 15, 'expected' => 15], // No incrementa (pasado límite)
        ];

        foreach ($users as $index => $user) {
            EloquentStudentDetail::factory()->forUser($user)->create([
                'semestre' => $details[$index]['semestre']
            ]);
        }

        // Act
        $incrementedCount = $this->repository->incrementSemesterForAll();

        // Assert
        $this->assertEquals(2, $incrementedCount); // Solo semestres 9 y 5 se incrementan

        foreach ($users as $index => $user) {
            $detail = EloquentStudentDetail::where('user_id', $user->id)->first();
            $this->assertEquals($details[$index]['expected'], $detail->semestre);
        }
    }

    #[Test]
    public function increment_semester_handles_users_without_details(): void
    {
        // Arrange
        $activeUser = ModelsUser::factory()->state(['status' => UserStatus::ACTIVO])->create();
        // No crear student_detail para este usuario

        // Act
        $incrementedCount = $this->repository->incrementSemesterForAll();

        // Assert
        $this->assertEquals(0, $incrementedCount);
    }

    // ==================== GET STUDENTS EXCEEDING SEMESTER LIMIT TESTS ====================

    #[Test]
    public function get_students_exceeding_semester_limit_returns_user_ids(): void
    {
        // Arrange
        $activeUser1 = ModelsUser::factory()->state(['status' => UserStatus::ACTIVO])->create();
        $activeUser2 = ModelsUser::factory()->state(['status' => UserStatus::ACTIVO])->create();
        $temporalUser = ModelsUser::factory()->state(['status' => UserStatus::BAJA_TEMPORAL])->create();
        $inactiveUser = ModelsUser::factory()->state(['status' => UserStatus::BAJA])->create();

        // Crear detalles con diferentes semestres
        EloquentStudentDetail::factory()->forUser($activeUser1)->create(['semestre' => 12]); // Excede límite (10)
        EloquentStudentDetail::factory()->forUser($activeUser2)->create(['semestre' => 8]);  // No excede
        EloquentStudentDetail::factory()->forUser($temporalUser)->create(['semestre' => 15]); // Excede límite
        EloquentStudentDetail::factory()->forUser($inactiveUser)->create(['semestre' => 20]); // No cuenta (inactivo)

        // Act
        $userIds = $this->repository->getStudentsExceedingSemesterLimit(10);

        // Assert
        $this->assertCount(2, $userIds);
        $this->assertContains($activeUser1->id, $userIds);
        $this->assertContains($temporalUser->id, $userIds);
        $this->assertNotContains($activeUser2->id, $userIds);
        $this->assertNotContains($inactiveUser->id, $userIds);
    }

    #[Test]
    public function get_students_exceeding_semester_limit_with_custom_limit(): void
    {
        // Arrange
        $activeUser1 = ModelsUser::factory()->state(['status' => UserStatus::ACTIVO])->create();
        $activeUser2 = ModelsUser::factory()->state(['status' => UserStatus::ACTIVO])->create();

        EloquentStudentDetail::factory()->forUser($activeUser1)->create(['semestre' => 6]);
        EloquentStudentDetail::factory()->forUser($activeUser2)->create(['semestre' => 8]);

        // Act - Con límite personalizado de 7
        $userIds = $this->repository->getStudentsExceedingSemesterLimit(7);

        // Assert
        $this->assertCount(1, $userIds);
        $this->assertContains($activeUser2->id, $userIds);
        $this->assertNotContains($activeUser1->id, $userIds);
    }

    #[Test]
    public function get_students_exceeding_semester_limit_returns_empty_array_when_none(): void
    {
        // Arrange
        $activeUser = ModelsUser::factory()->state(['status' => UserStatus::ACTIVO])->create();
        EloquentStudentDetail::factory()->forUser($activeUser)->create(['semestre' => 5]);

        // Act
        $userIds = $this->repository->getStudentsExceedingSemesterLimit(10);

        // Assert
        $this->assertEmpty($userIds);
    }

    // ==================== UPDATE STUDENT DETAILS TESTS ====================

    #[Test]
    public function update_student_details_successfully(): void
    {
        // Arrange
        $user = ModelsUser::factory()->create();
        $detail = EloquentStudentDetail::factory()->forUser($user)->create([
            'semestre' => 5,
            'group' => 'A',
        ]);

        $newCareer = \App\Models\Career::factory()->create();
        $updateFields = [
            'career_id' => $newCareer->id,
            'semestre' => 6,
            'group' => 'B',
            'workshop' => 'Nuevo Taller',
        ];

        // Act
        $result = $this->repository->updateStudentDetails($user->id, $updateFields);

        // Assert
        $this->assertInstanceOf(DomainUser::class, $result);

        // Verificar que los detalles se actualizaron
        $detail->refresh();
        $this->assertEquals($newCareer->id, $detail->career_id);
        $this->assertEquals(6, $detail->semestre);
        $this->assertEquals('B', $detail->group);
        $this->assertEquals('Nuevo Taller', $detail->workshop);

        // Verificar que el usuario tiene los detalles cargados
        $this->assertNotNull($result->studentDetail);
    }

    #[Test]
    public function update_student_details_partial_fields(): void
    {
        // Arrange
        $user = ModelsUser::factory()->create();
        $detail = EloquentStudentDetail::factory()->forUser($user)->create([
            'semestre' => 5,
            'group' => 'A',
            'workshop' => 'Taller Antiguo',
        ]);

        $updateFields = [
            'semestre' => 6,
        ];

        // Act
        $result = $this->repository->updateStudentDetails($user->id, $updateFields);

        // Assert
        $this->assertInstanceOf(DomainUser::class, $result);

        $detail->refresh();
        $this->assertEquals(6, $detail->semestre);
        $this->assertEquals('A', $detail->group); // No cambió
        $this->assertEquals('Taller Antiguo', $detail->workshop); // No cambió
    }

    #[Test]
    public function update_student_details_throws_exception_when_not_found(): void
    {
        // Arrange
        $user = ModelsUser::factory()->create();
        // No crear student_detail

        $updateFields = ['semestre' => 6];

        // Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $this->repository->updateStudentDetails($user->id, $updateFields);
    }

    #[Test]
    public function update_student_details_with_null_fields(): void
    {
        // Arrange
        $user = ModelsUser::factory()->create();
        $detail = EloquentStudentDetail::factory()->forUser($user)->create([
            'group' => 'A',
            'workshop' => 'Taller',
        ]);

        $updateFields = [
            'group' => null,
            'workshop' => null,
        ];

        // Act
        $result = $this->repository->updateStudentDetails($user->id, $updateFields);

        // Assert
        $detail->refresh();
        $this->assertNull($detail->group);
        $this->assertNull($detail->workshop);
    }

    // ==================== ATTACH STUDENT DETAIL TESTS ====================

    #[Test]
    public function attach_student_detail_creates_detail_and_assigns_role(): void
    {
        // Arrange
        $user = ModelsUser::factory()->create();
        $career = \App\Models\Career::factory()->create();

        $detailDTO = new CreateStudentDetailDTO(
            user_id: $user->id,
            career_id: $career->id,
            n_control: 20230001,
            semestre: 5,
            group: 'A',
            workshop: 'Taller de Programación'
        );

        // Act
        $result = $this->repository->attachStudentDetail($detailDTO, $user);

        // Assert
        $this->assertInstanceOf(DomainUser::class, $result);

        // Verificar que se creó el detalle
        $this->assertDatabaseHas('student_details', [
            'user_id' => $user->id,
            'n_control' => 20230001,
            'semestre' => 5,
        ]);

        // Verificar que se asignó el rol de estudiante
        $user->refresh();
        $this->assertTrue($user->hasRole(UserRoles::STUDENT->value));

        // Verificar que el usuario tiene los detalles cargados en el domain object
        $this->assertNotNull($result->studentDetail);
    }

    #[Test]
    public function attach_student_detail_with_minimal_data(): void
    {
        // Arrange
        $user = ModelsUser::factory()->create();

        $detailDTO = new CreateStudentDetailDTO(
            user_id: $user->id,
            career_id: null,
            n_control: null,
            semestre: 1,
            group: null,
            workshop: null
        );

        // Act
        $result = $this->repository->attachStudentDetail($detailDTO, $user);

        // Assert
        $this->assertInstanceOf(DomainUser::class, $result);

        $this->assertDatabaseHas('student_details', [
            'user_id' => $user->id,
            'semestre' => 1,
            'n_control' => null,
        ]);

        $user->refresh();
        $this->assertTrue($user->hasRole(UserRoles::STUDENT->value));
    }

    #[Test]
    public function attach_student_detail_does_not_duplicate_role(): void
    {
        // Arrange
        $user = ModelsUser::factory()->create();
        // Ya tiene rol de estudiante
        $user->assignRole(UserRoles::STUDENT->value);

        $detailDTO = new CreateStudentDetailDTO(
            user_id: $user->id,
            career_id: null,
            n_control: null,
            semestre: 1
        );

        // Act
        $result = $this->repository->attachStudentDetail($detailDTO, $user);

        // Assert
        $this->assertInstanceOf(DomainUser::class, $result);

        // El rol de estudiante debería seguir asignado
        $user->refresh();
        $this->assertTrue($user->hasRole(UserRoles::STUDENT->value));

        // Verificar que no tiene roles duplicados
        $studentRoleCount = DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->where('role_id', function ($query) {
                $query->select('id')
                    ->from('roles')
                    ->where('name', UserRoles::STUDENT->value);
            })
            ->count();

        $this->assertEquals(1, $studentRoleCount);
    }

    // ==================== COMPREHENSIVE TESTS ====================

    #[Test]
    public function complete_student_detail_lifecycle(): void
    {
        // Test completo del ciclo de vida de detalles de estudiante

        // 1. Crear usuario sin detalles
        $user = ModelsUser::factory()->create();

        // 2. Verificar que no tiene detalles
        $initialDetails = $this->repository->findStudentDetails($user->id);
        $this->assertNull($initialDetails);

        // 3. Attach detalle
        $career = \App\Models\Career::factory()->create();
        $detailDTO = new CreateStudentDetailDTO(
            user_id: $user->id,
            career_id: $career->id,
            n_control: 20230001,
            semestre: 1,
            group: 'A'
        );

        $userWithDetail = $this->repository->attachStudentDetail($detailDTO, $user);
        $this->assertInstanceOf(DomainUser::class, $userWithDetail);

        // 4. Verificar que ahora tiene detalles
        $foundDetails = $this->repository->findStudentDetails($user->id);
        $this->assertInstanceOf(StudentDetail::class, $foundDetails);
        $this->assertEquals(1, $foundDetails->semestre);

        // 5. Actualizar detalles
        $updatedUser = $this->repository->updateStudentDetails($user->id, [
            'semestre' => 2,
            'group' => 'B',
        ]);

        // 6. Verificar actualización
        $updatedDetails = $this->repository->findStudentDetails($user->id);
        $this->assertEquals(2, $updatedDetails->semestre);
        $this->assertEquals('B', $updatedDetails->group);
    }

    #[Test]
    public function bulk_operations_with_student_details(): void
    {
        // Arrange - Crear múltiples estudiantes
        $users = ModelsUser::factory()->count(5)->state(['status' => UserStatus::ACTIVO])->create();
        $career = \App\Models\Career::factory()->create();

        // 1. Insertar detalles en masa
        $studentDetails = [];
        $controlNumber = 20230001;

        foreach ($users as $user) {
            $studentDetails[] = [
                'user_id' => $user->id,
                'career_id' => $career->id,
                'n_control' => $controlNumber++,
                'semestre' => rand(1, 8),
                'group' => 'A',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $insertedCount = $this->repository->insertStudentDetails($studentDetails);
        $this->assertEquals(5, $insertedCount);

        // 2. Incrementar semestres
        $incrementedCount = $this->repository->incrementSemesterForAll();
        $this->assertGreaterThan(0, $incrementedCount);

        // 3. Buscar estudiantes que excedan límite (si los hay)
        $exceedingUsers = $this->repository->getStudentsExceedingSemesterLimit(10);
        $this->assertIsArray($exceedingUsers);

        // 4. Actualizar detalles de un estudiante específico
        $firstUser = $users[0];
        $updatedUser = $this->repository->updateStudentDetails($firstUser->id, [
            'group' => 'B',
            'workshop' => 'Taller Especial',
        ]);

        $this->assertInstanceOf(DomainUser::class, $updatedUser);
        $this->assertDatabaseHas('student_details', [
            'user_id' => $firstUser->id,
            'group' => 'B',
        ]);
    }

    #[Test]
    public function student_detail_domain_object_methods(): void
    {
        // Test para métodos del domain object StudentDetail

        // Arrange
        $detail = new StudentDetail(
            user_id: 1,
            career_id: 10,
            n_control: 20230001,
            semestre: 5,
            group: 'A',
            workshop: 'Taller'
        );

        // Test promote() method
        $initialSemester = $detail->semestre;
        $detail->promote();
        $this->assertEquals($initialSemester + 1, $detail->semestre);

        // Test promote() when semester is null
        $detailWithoutSemester = new StudentDetail(
            user_id: 2,
            semestre: null
        );
        $detailWithoutSemester->promote();
        $this->assertNull($detailWithoutSemester->semestre);

        // Test toArray() method
        $array = $detail->toArray();
        $this->assertIsArray($array);
        $this->assertEquals(1, $array['user_id']);
        $this->assertEquals(10, $array['career_id']);
        $this->assertEquals(20230001, $array['n_control']);
        $this->assertEquals(6, $array['semestre']); // Después del promote
        $this->assertEquals('A', $array['group']);
        $this->assertEquals('Taller', $array['workshop']);
    }

    #[Test]
    public function repository_handles_large_number_of_students(): void
    {
        // Arrange - Crear 100 estudiantes
        $users = ModelsUser::factory()->count(100)->state(['status' => UserStatus::ACTIVO])->create();
        $career = \App\Models\Career::factory()->create();

        // Insertar detalles en masa
        $studentDetails = [];
        $controlNumber = 20230001;

        foreach ($users as $user) {
            $studentDetails[] = [
                'user_id' => $user->id,
                'career_id' => $career->id,
                'n_control' => $controlNumber++,
                'semestre' => rand(1, 12),
                'group' => chr(rand(65, 70)), // A-F
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Act
        $insertedCount = $this->repository->insertStudentDetails($studentDetails);

        // Assert
        $this->assertEquals(100, $insertedCount);

        // Incrementar semestres
        $incrementedCount = $this->repository->incrementSemesterForAll();
        $this->assertLessThanOrEqual(100, $incrementedCount);

        // Buscar estudiantes que excedan límite
        $exceedingUsers = $this->repository->getStudentsExceedingSemesterLimit(10);
        $this->assertIsArray($exceedingUsers);

        // Todos los estudiantes deben tener detalles
        $totalStudentDetails = EloquentStudentDetail::count();
        $this->assertEquals(100, $totalStudentDetails);
    }

}
