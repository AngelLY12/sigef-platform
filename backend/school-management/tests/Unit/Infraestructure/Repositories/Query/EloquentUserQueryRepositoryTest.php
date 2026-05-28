<?php

namespace Tests\Unit\Infraestructure\Repositories\Query;

use App\Core\Application\DTO\Response\User\UserAuthResponse;
use App\Core\Application\DTO\Response\User\UserIdListDTO;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Entities\Role;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Infraestructure\Mappers\PaymentConceptMapper;
use App\Models\Career;
use App\Models\StudentDetail;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role as SpatieRole;
use App\Models\User as EloquentUser;

use App\Core\Infraestructure\Repositories\Query\User\EloquentUserQueryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentUserQueryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUserQueryRepository $repository;
    private int $counter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear roles necesarios
        SpatieRole::create(['name' => UserRoles::ADMIN->value, 'guard_name' => 'web']);
        SpatieRole::create(['name' => UserRoles::STUDENT->value, 'guard_name' => 'web']);
        SpatieRole::create(['name' => UserRoles::PARENT->value, 'guard_name' => 'web']);
        SpatieRole::create(['name' => UserRoles::APPLICANT->value, 'guard_name' => 'web']);

        $this->repository = new EloquentUserQueryRepository();
        $this->counter = 0;
    }

    // Método helper para crear PaymentConcept de prueba
    private function createPaymentConcept(
        string $appliesTo,
        array $careerIds = [],
        array $exceptionIds = [],
        array $userIds = [],
        array $semesters = [],
        array $applicantTags = []
    ): PaymentConcept {
        /** @var \App\Models\PaymentConcept $modelConcept */
        $modelConcept=\App\Models\PaymentConcept::factory()->create([
            'applies_to' => $appliesTo,
        ]);
        $domainConcept= PaymentConceptMapper::toDomain($modelConcept);
        $domainConcept->setUserIds($userIds);
        $domainConcept->setSemesters($semesters);
        $domainConcept->setExceptionUsersIds($exceptionIds);
        $domainConcept->setCareerIds($careerIds);
        $domainConcept->setApplicantTag($applicantTags);
        return $domainConcept;
    }

    #[Test]
    public function find_by_id_successfully(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();
        $role = SpatieRole::where('name', UserRoles::STUDENT->value)->first();
        $user->assignRole($role);

        // Act
        $result = $this->repository->findById($user->id);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
        $this->assertEquals($user->email, $result->email);
        $this->assertCount(1, $result->roles);
    }

    #[Test]
    public function find_by_id_returns_null_for_nonexistent_user(): void
    {
        // Act
        $result = $this->repository->findById(999999);

        // Assert
        $this->assertNull($result);
    }

    // ==================== FIND USER ROLES TESTS ====================

    #[Test]
    public function find_user_roles_successfully(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();
        $user->assignRole($studentRole);

        // Act
        $result = $this->repository->findUserRoles($user->id);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertInstanceOf(Role::class, $result[0]);
        $this->assertEquals(UserRoles::STUDENT->value, $result[0]->name);
    }

    // ==================== GET USER WITH STUDENT DETAIL TESTS ====================

    #[Test]
    public function get_user_with_student_detail_successfully(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();
        $career = Career::factory()->create();
        $studentDetail = StudentDetail::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id
        ]);

        // Act
        $result = $this->repository->getUserWithStudentDetail($user->id);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertNotNull($result->studentDetail);
        $this->assertEquals($studentDetail->n_control, $result->studentDetail->n_control);
    }

    // ==================== GET USER BY STRIPE CUSTOMER TESTS ====================

    #[Test]
    public function get_user_by_stripe_customer_successfully(): void
    {
        // Arrange
        $stripeCustomerId = 'cus_' . uniqid();
        $user = EloquentUser::factory()->create([
            'stripe_customer_id' => $stripeCustomerId
        ]);

        // Act
        $result = $this->repository->getUserByStripeCustomer($stripeCustomerId);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
        $this->assertEquals($stripeCustomerId, $result->stripe_customer_id);
    }

    #[Test]
    public function get_user_by_stripe_customer_throws_exception_for_nonexistent(): void
    {
        // Assert
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        // Act
        $this->repository->getUserByStripeCustomer('cus_nonexistent');
    }

    // ==================== FIND USER BY EMAIL TESTS ====================

    #[Test]
    public function find_user_by_email_successfully(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();

        // Act
        $result = $this->repository->findUserByEmail($user->email);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
        $this->assertEquals($user->email, $result->email);
    }

    #[Test]
    public function find_user_by_email_returns_null_for_nonexistent(): void
    {
        // Act
        $result = $this->repository->findUserByEmail('nonexistent@example.com');

        // Assert
        $this->assertNull($result);
    }

    // ==================== FIND BY SEARCH TESTS ====================

    #[Test]
    public function find_by_search_with_curp(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();
        // Crear studentDetail para evitar error en el mapper
        StudentDetail::factory()->create(['user_id' => $user->id]);

        // Act
        $result = $this->repository->findBySearch($user->curp);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
        $this->assertEquals($user->curp, $result->curp);
    }

    #[Test]
    public function find_by_search_with_email(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();
        // Crear studentDetail para evitar error en el mapper
        StudentDetail::factory()->create(['user_id' => $user->id]);

        // Act
        $result = $this->repository->findBySearch($user->email);

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
        $this->assertEquals($user->email, $result->email);
    }

    #[Test]
    public function find_by_search_with_control_number(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();
        $career = Career::factory()->create();
        $studentDetail = StudentDetail::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id,
            'n_control' => 'CONTROL123'
        ]);

        // Act
        $result = $this->repository->findBySearch('CONTROL123');

        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
    }

    #[Test]
    public function find_by_search_returns_null_when_no_match(): void
    {
        // Act
        $result = $this->repository->findBySearch('nonexistent123');

        // Assert
        $this->assertNull($result);
    }

    // ==================== GET USER IDS BY CONTROL NUMBERS TESTS ====================

    #[Test]
    public function get_user_ids_by_control_numbers_successfully(): void
    {
        // Arrange
        $career = Career::factory()->create();

        $user1 = EloquentUser::factory()->create(['status' => UserStatus::ACTIVO]);
        $studentDetail1 = StudentDetail::factory()->create([
            'user_id' => $user1->id,
            'career_id' => $career->id,
            'n_control' => 'CONTROL001'
        ]);

        $user2 = EloquentUser::factory()->create(['status' => UserStatus::ACTIVO]);
        $studentDetail2 = StudentDetail::factory()->create([
            'user_id' => $user2->id,
            'career_id' => $career->id,
            'n_control' => 'CONTROL002'
        ]);

        // Usuario inactivo
        $user3 = EloquentUser::factory()->create(['status' => UserStatus::BAJA]);
        $studentDetail3 = StudentDetail::factory()->create([
            'user_id' => $user3->id,
            'career_id' => $career->id,
            'n_control' => 'CONTROL003'
        ]);

        $controlNumbers = ['CONTROL001', 'CONTROL002', 'CONTROL003'];

        // Act
        $result = $this->repository->getUserIdsByControlNumbers($controlNumbers);

        // Assert
        $this->assertInstanceOf(UserIdListDTO::class, $result);
        $this->assertContains($user1->id, $result->userIds);
        $this->assertContains($user2->id, $result->userIds);
        $this->assertNotContains($user3->id, $result->userIds);
        $this->assertCount(2, $result->userIds);
    }

    // ==================== COUNT STUDENTS TESTS ====================

    #[Test]
    public function count_students_all_time(): void
    {
        // Arrange
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();

        // Estudiantes activos
        for ($i = 0; $i < 5; $i++) {
            $user = EloquentUser::factory()->create(['status' => UserStatus::ACTIVO]);
            $user->assignRole($studentRole);
        }

        // Estudiantes inactivos
        for ($i = 0; $i < 2; $i++) {
            $user = EloquentUser::factory()->create(['status' => UserStatus::BAJA]);
            $user->assignRole($studentRole);
        }

        // Act
        $result = $this->repository->countStudents(false);

        // Assert
        $this->assertEquals(5, $result);
    }

    // ==================== FIND ACTIVE STUDENTS TESTS ====================

    #[Test]
    public function find_active_students_without_search(): void
    {
        // Arrange
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();

        // Estudiantes activos
        for ($i = 0; $i < 10; $i++) {
            $user = EloquentUser::factory()->create(['status' => UserStatus::ACTIVO]);
            $user->assignRole($studentRole);
        }

        // Estudiantes inactivos
        for ($i = 0; $i < 3; $i++) {
            $user = EloquentUser::factory()->create(['status' => UserStatus::BAJA]);
            $user->assignRole($studentRole);
        }

        // Act
        $result = $this->repository->findActiveStudents(null, 15, 1);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(10, $result->total());
        $this->assertCount(10, $result->items());
    }

    #[Test]
    public function find_active_students_with_search(): void
    {
        // Arrange
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();

        // Usuario específico
        $user1 = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO,
            'curp' => 'CURP_UNICO_123'
        ]);
        $user1->assignRole($studentRole);

        // Otros usuarios
        for ($i = 0; $i < 3; $i++) {
            $user = EloquentUser::factory()->create(['status' => UserStatus::ACTIVO]);
            $user->assignRole($studentRole);
        }

        // Act - Buscar por CURP específico
        $result = $this->repository->findActiveStudents('CURP_UNICO', 10, 1);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());
        $this->assertEquals($user1->id, $result->items()[0]->id);
    }

    // ==================== GET RECIPIENTS TESTS ====================

    #[Test]
    public function get_recipients_for_all_students(): void
    {
        // Arrange
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();

        // Crear estudiantes activos
        $students = [];
        for ($i = 0; $i < 5; $i++) {
            $user = EloquentUser::factory()->create(['status' => UserStatus::ACTIVO]);
            $user->assignRole($studentRole);
            $students[] = $user;
        }

        // Crear usuarios no estudiantes
        EloquentUser::factory()->count(3)->create(['status' => UserStatus::ACTIVO]);

        $concept = $this->createPaymentConcept(PaymentConceptAppliesTo::TODOS->value);

        // Act
        $result = $this->repository->getRecipients($concept, PaymentConceptAppliesTo::TODOS->value);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(5, $result);
    }

    #[Test]
    public function get_recipients_for_career(): void
    {
        // Arrange
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();
        $career1 = Career::factory()->create();
        $career2 = Career::factory()->create();

        // Estudiantes de carrera 1
        for ($i = 0; $i < 3; $i++) {
            $user = EloquentUser::factory()->create(['status' => UserStatus::ACTIVO]);
            $user->assignRole($studentRole);
            StudentDetail::factory()->create([
                'user_id' => $user->id,
                'career_id' => $career1->id
            ]);
        }

        // Estudiantes de carrera 2
        for ($i = 0; $i < 2; $i++) {
            $user = EloquentUser::factory()->create(['status' => UserStatus::ACTIVO]);
            $user->assignRole($studentRole);
            StudentDetail::factory()->create([
                'user_id' => $user->id,
                'career_id' => $career2->id
            ]);
        }

        $concept = $this->createPaymentConcept(PaymentConceptAppliesTo::CARRERA->value, [$career1->id]);

        // Act
        $result = $this->repository->getRecipients($concept, PaymentConceptAppliesTo::CARRERA->value);

        // Assert
        $this->assertCount(3, $result);
    }

    #[Test]
    public function get_recipients_for_semester(): void
    {
        // Arrange
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();

        // Estudiantes semestre 5
        for ($i = 0; $i < 2; $i++) {
            $user = EloquentUser::factory()->create(['status' => UserStatus::ACTIVO]);
            $user->assignRole($studentRole);
            StudentDetail::factory()->create([
                'user_id' => $user->id,
                'semestre' => 5
            ]);
        }

        // Estudiantes semestre 3
        for ($i = 0; $i < 3; $i++) {
            $user = EloquentUser::factory()->create(['status' => UserStatus::ACTIVO]);
            $user->assignRole($studentRole);
            StudentDetail::factory()->create([
                'user_id' => $user->id,
                'semestre' => 3
            ]);
        }

        $concept = $this->createPaymentConcept(PaymentConceptAppliesTo::SEMESTRE->value, [], [], [], [5]);

        // Act
        $result = $this->repository->getRecipients($concept, PaymentConceptAppliesTo::SEMESTRE->value);

        // Assert
        $this->assertCount(2, $result);
    }

    #[Test]
    public function get_recipients_ids_successfully(): void
    {
        // Arrange
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();

        for ($i = 0; $i < 3; $i++) {
            $user = EloquentUser::factory()->create(['status' => UserStatus::ACTIVO]);
            $user->assignRole($studentRole);
        }

        $concept = $this->createPaymentConcept(PaymentConceptAppliesTo::TODOS->value);

        // Act
        $result = $this->repository->getRecipientsIds($concept, PaymentConceptAppliesTo::TODOS->value);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }

    #[Test]
    public function has_any_recipient_returns_true(): void
    {
        // Arrange
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();
        $user = EloquentUser::factory()->create(['status' => UserStatus::ACTIVO]);
        $user->assignRole($studentRole);

        $concept = $this->createPaymentConcept(PaymentConceptAppliesTo::TODOS->value);

        // Act
        $result = $this->repository->hasAnyRecipient($concept, PaymentConceptAppliesTo::TODOS->value);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function get_recipients_from_ids_successfully(): void
    {
        // Arrange
        $users = EloquentUser::factory()
            ->count(3)
            ->create(['status' => UserStatus::ACTIVO]);

        $ids = $users->pluck('id')->toArray();

        // Act
        $result = $this->repository->getRecipientsFromIds($ids);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        foreach ($result as $recipient) {
            $this->assertObjectHasProperty('id', $recipient);
            $this->assertObjectHasProperty('fullName', $recipient);
            $this->assertObjectHasProperty('email', $recipient);
        }
    }

    // ==================== HAS ROLE TESTS ====================

    #[Test]
    public function has_role_true(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();
        $user->assignRole($studentRole);

        // Act
        $result = $this->repository->hasRole($user->id, UserRoles::STUDENT->value);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function get_students_with_pending_summary_successfully(): void
    {
        // Arrange
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();
        $career = Career::factory()->create();

        $user = EloquentUser::factory()->create(['status' => UserStatus::ACTIVO]);
        $user->assignRole($studentRole);
        $studentDetail = StudentDetail::factory()->create([
            'user_id' => $user->id,
            'career_id' => $career->id,
            'semestre' => 5
        ]);

        $concept = $this->createPaymentConcept(PaymentConceptAppliesTo::TODOS->value);

        // Crear un concepto de pago pendiente si es necesario

        // Act
        $result = $this->repository->getStudentsWithPendingSummary([$user->id]);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    // ==================== FIND ALL USERS TESTS ====================

    #[Test]
    public function find_all_users_without_status_filter(): void
    {
        // Arrange
        $adminRole = SpatieRole::where('name', UserRoles::ADMIN->value)->first();
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();

        // Usuarios no-admin
        for ($i = 0; $i < 8; $i++) {
            $user = EloquentUser::factory()->create();
            if ($i < 5) {
                $user->assignRole($studentRole);
            }
        }

        // Usuario admin
        $admin = EloquentUser::factory()->create();
        $admin->assignRole($adminRole);

        // Act
        $result = $this->repository->findAllUsers(10, 1, null);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(8, $result->total()); // Solo los no-admin
    }

    #[Test]
    public function find_all_users_with_status_filter(): void
    {
        // Arrange
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();

        // Usuarios activos
        for ($i = 0; $i < 3; $i++) {
            $user = EloquentUser::factory()->create(['status' => UserStatus::ACTIVO]);
            $user->assignRole($studentRole);
        }

        // Usuarios inactivos
        for ($i = 0; $i < 2; $i++) {
            $user = EloquentUser::factory()->create(['status' => UserStatus::BAJA]);
            $user->assignRole($studentRole);
        }

        // Act - Buscar solo activos
        $result = $this->repository->findAllUsers(10, 1, UserStatus::ACTIVO);

        // Assert
        $this->assertEquals(3, $result->total());

        foreach ($result->items() as $item) {
            $this->assertEquals(UserStatus::ACTIVO->value, $item->status);
        }
    }

    // ==================== FIND AUTH USER TESTS ====================

    #[Test]
    public function find_auth_user_successfully(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create(

        );
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();
        $user->assignRole($studentRole);

        // Crear studentDetail para evitar errores en el mapper
        StudentDetail::factory()->create(['user_id' => $user->id]);

        Auth::login($user);

        // Act
        $result = $this->repository->findAuthUser();

        // Assert
        $this->assertInstanceOf(UserAuthResponse::class, $result);
        $this->assertEquals($user->id, $result->id);
    }

    #[Test]
    public function find_auth_user_returns_null_when_not_authenticated(): void
    {
        // Arrange
        Auth::logout();

        // Act
        $result = $this->repository->findAuthUser();

        // Assert
        $this->assertNull($result);
    }

    // ==================== FIND BY IDS TESTS ====================

    #[Test]
    public function find_by_ids_successfully(): void
    {
        // Arrange
        $users = [
            EloquentUser::factory()->create(),
            EloquentUser::factory()->create(),
            EloquentUser::factory()->create(),
        ];

        $ids = [$users[0]->id, $users[1]->id];

        // Act
        $result = $this->repository->findByIds($ids);

        // Assert
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(2, $result);

        foreach ($result as $user) {
            $this->assertInstanceOf(User::class, $user);
            $this->assertContains($user->id, $ids);
        }
    }

    // ==================== FIND MODEL ENTITY TESTS ====================

    #[Test]
    public function find_model_entity_successfully(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();

        // Act
        $result = $this->repository->findModelEntity($user->id);

        // Assert
        $this->assertInstanceOf(EloquentUser::class, $result);
        $this->assertEquals($user->id, $result->id);
    }

    // ==================== GET USERS BY ROLE CURSOR TESTS ====================

    #[Test]
    public function get_users_by_role_cursor(): void
    {
        // Arrange
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();

        for ($i = 0; $i < 3; $i++) {
            $user = EloquentUser::factory()->create();
            $user->assignRole($studentRole);
        }

        // Act
        $generator = $this->repository->getUsersByRoleCursor(UserRoles::STUDENT->value);
        $results = iterator_to_array($generator);

        // Assert
        $this->assertCount(3, $results);

        foreach ($results as $user) {
            $this->assertInstanceOf(EloquentUser::class, $user);
            $this->assertTrue($user->hasRole(UserRoles::STUDENT->value));
        }
    }

    // ==================== USER HAS UNREAD NOTIFICATIONS TESTS ====================

    #[Test]
    public function user_has_unread_notifications_true(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();

        DB::table('notifications')->insert([
            'id' => uniqid(),
            'type' => 'TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['message' => 'Test']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act
        $result = $this->repository->userHasUnreadNotifications($user->id);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function user_has_unread_notifications_false(): void
    {
        // Arrange
        $user = EloquentUser::factory()->create();

        DB::table('notifications')->insert([
            'id' => uniqid(),
            'type' => 'TestNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['message' => 'Test']),
            'read_at' => now(), // Leída
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Act
        $result = $this->repository->userHasUnreadNotifications($user->id);

        // Assert
        $this->assertFalse($result);
    }

    // ==================== INTEGRATION TESTS ====================

    #[Test]
    public function complete_user_query_scenarios(): void
    {
        // 1. Crear datos de prueba
        $studentRole = SpatieRole::where('name', UserRoles::STUDENT->value)->first();
        $career = Career::factory()->create();

        // Crear usuario con diferentes características
        $student = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO
        ]);
        $student->assignRole($studentRole);
        $studentDetail = StudentDetail::factory()->create([
            'user_id' => $student->id,
            'career_id' => $career->id,
            'n_control' => 'CONTROL001',
            'semestre' => 5
        ]);

        $parent = EloquentUser::factory()->create([
            'status' => UserStatus::ACTIVO
        ]);
        $parentRole = SpatieRole::where('name', UserRoles::PARENT->value)->first();
        $parent->assignRole($parentRole);

        // 2. Probar findById
        $foundUser = $this->repository->findById($student->id);
        $this->assertEquals($student->id, $foundUser->id);

        // 3. Probar findUserByEmail
        $byEmail = $this->repository->findUserByEmail($student->email);
        $this->assertEquals($student->id, $byEmail->id);

        // 4. Probar getUserWithStudentDetail
        $withDetail = $this->repository->getUserWithStudentDetail($student->id);
        $this->assertEquals('CONTROL001', $withDetail->studentDetail->n_control);

        // 5. Probar findBySearch con CURP
        $bySearchCurp = $this->repository->findBySearch($student->curp);
        $this->assertEquals($student->id, $bySearchCurp->id);

        // 6. Probar findBySearch con control number
        $bySearchControl = $this->repository->findBySearch('CONTROL001');
        $this->assertEquals($student->id, $bySearchControl->id);

        // 7. Probar countStudents
        $studentCount = $this->repository->countStudents(false);
        $this->assertEquals(1, $studentCount);

        // 8. Probar getUserIdsByControlNumbers
        $idsByControl = $this->repository->getUserIdsByControlNumbers(['CONTROL001']);
        $this->assertContains($student->id, $idsByControl->userIds);

        // 9. Probar findActiveStudents
        $activeStudents = $this->repository->findActiveStudents(null, 10, 1);
        $this->assertEquals(1, $activeStudents->total());

        // 10. Probar hasRole
        $this->assertTrue($this->repository->hasRole($student->id, UserRoles::STUDENT->value));
        $this->assertFalse($this->repository->hasRole($student->id, UserRoles::PARENT->value));

        // 11. Probar findAllUsers (sin admin)
        $allUsers = $this->repository->findAllUsers(10, 1, null);
        $this->assertEquals(2, $allUsers->total());

        // 12. Probar findByIds
        $usersByIds = $this->repository->findByIds([$student->id]);
        $this->assertCount(1, $usersByIds);

        // 13. Probar getRecipients para carrera
        $conceptCareer = $this->createPaymentConcept(PaymentConceptAppliesTo::CARRERA->value, [$career->id]);
        $recipientsCareer = $this->repository->getRecipients($conceptCareer, PaymentConceptAppliesTo::CARRERA->value);
        $this->assertCount(1, $recipientsCareer);

        // 14. Probar hasAnyRecipient
        $this->assertTrue($this->repository->hasAnyRecipient($conceptCareer, PaymentConceptAppliesTo::CARRERA->value));
    }

    #[Test]
    public function edge_cases_and_error_handling(): void
    {
        // 1. Probar con valores límite
        $nullResult = $this->repository->findById(0);
        $this->assertNull($nullResult);

        $negativeResult = $this->repository->findById(-1);
        $this->assertNull($negativeResult);

        // 2. Probar búsqueda con cadena vacía
        $emptySearch = $this->repository->findBySearch('');
        $this->assertNull($emptySearch);

        // 3. Probar findByIds con array vacío
        $emptyIds = $this->repository->findByIds([]);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $emptyIds);
        $this->assertTrue($emptyIds->isEmpty());

        // 4. Probar getUserIdsByControlNumbers con array vacío
        $emptyControl = $this->repository->getUserIdsByControlNumbers([]);
        $this->assertInstanceOf(UserIdListDTO::class, $emptyControl);
        $this->assertEmpty($emptyControl->userIds);

        // 5. Probar getRecipientsFromIds con array vacío
        $emptyRecipients = $this->repository->getRecipientsFromIds([]);
        $this->assertIsArray($emptyRecipients);
        $this->assertEmpty($emptyRecipients);

        // 6. Probar getStudentsWithPendingSummary con array vacío
        $emptyPending = $this->repository->getStudentsWithPendingSummary([]);
        $this->assertIsArray($emptyPending);
        $this->assertEmpty($emptyPending);

        // 7. Probar findModelEntity con ID inexistente
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->findModelEntity(999999);

        // 8. Probar getUserByStripeCustomer con ID inexistente
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->repository->getUserByStripeCustomer('cus_nonexistent');
    }

}
