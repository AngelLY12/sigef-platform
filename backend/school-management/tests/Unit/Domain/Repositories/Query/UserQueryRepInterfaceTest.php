<?php

namespace Tests\Unit\Domain\Repositories\Query;

use Tests\Stubs\Repositories\Query\UserQueryRepStub;
use Tests\Unit\Domain\Repositories\BaseRepositoryTestCase;
use App\Core\Domain\Repositories\Query\User\UserQueryRepInterface;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Entities\Role;
use App\Core\Domain\Entities\PaymentConcept;
use App\Core\Domain\Entities\StudentDetail;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserGender;
use App\Core\Domain\Enum\User\UserBloodType;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptAppliesTo;
use App\Core\Domain\Enum\PaymentConcept\PaymentConceptApplicantType;
use App\Core\Application\DTO\Response\User\UserAuthResponse;
use App\Core\Application\DTO\Response\User\UserIdListDTO;
use App\Core\Application\DTO\Response\User\UserRecipientDTO;
use App\Core\Application\DTO\Response\User\UserWithPendingSumamaryResponse;
use App\Models\User as ModelsUser;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Generator;
use PHPUnit\Framework\Attributes\Test;

class UserQueryRepInterfaceTest extends BaseRepositoryTestCase
{
    protected string $interfaceClass = UserQueryRepInterface::class;
    private PaymentConcept $testPaymentConcept;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserQueryRepStub();

        // Crear un PaymentConcept de prueba
        $this->testPaymentConcept = new PaymentConcept(
            concept_name: 'Test Concept',
            status: PaymentConceptStatus::ACTIVO,
            start_date: Carbon::parse('2025-01-01'),
            amount: '1000.00',
            applies_to: PaymentConceptAppliesTo::TODOS,
            userIds: [1, 2, 3],
            careerIds: [1, 2],
            semesters: [1, 2],
            exceptionUserIds: [4],
            applicantTags: [PaymentConceptApplicantType::APPLICANT->value],
            id: 1,
            description: 'Test description',
            end_date: Carbon::parse('2025-12-31')
        );
    }

    #[Test]
    public function it_can_be_instantiated(): void
    {
        $this->assertNotNull($this->repository);
        $this->assertImplementsInterface($this->interfaceClass);
    }

    #[Test]
    public function it_has_all_required_methods(): void
    {
        $methods = [
            'findUserByEmail',
            'findById',
            'getUserWithStudentDetail',
            'getUserByStripeCustomer',
            'getUserIdsByControlNumbers',
            'countStudents',
            'findActiveStudents',
            'findBySearch',
            'getRecipients',
            'getRecipientsIds',
            'getRecipientsFromIds',
            'hasAnyRecipient',
            'hasRole',
            'getStudentsWithPendingSummary',
            'findAllUsers',
            'findAuthUser',
            'findByIds',
            'findUserRoles',
            'findModelEntity',
            'getUsersByRoleCursor',
            'getUsersByCurpCursor',
            'userHasUnreadNotifications'
        ];

        foreach ($methods as $method) {
            $this->assertMethodExists($method);
        }
    }

    #[Test]
    public function findUserByEmail_returns_user_when_found(): void
    {
        $user = new User(
            curp: 'TEST123456ABCDEFG0',
            name: 'Juan',
            last_name: 'Pérez',
            email: 'juan@example.com',
            password: 'password',
            phone_number: '1234567890',
            status: UserStatus::ACTIVO,
            roles: [new Role(2, UserRoles::STUDENT->value)],
            id: 1
        );

        $this->repository->setNextFindUserByEmailResult($user);

        $result = $this->repository->findUserByEmail('juan@example.com');

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('juan@example.com', $result->email);
        $this->assertEquals('Juan', $result->name);
        $this->assertTrue($result->isActive());
        $this->assertTrue($result->isStudent());
    }

    #[Test]
    public function findById_returns_user_when_found(): void
    {
        $user = new User(
            curp: 'TEST654321ABCDEFG0',
            name: 'María',
            last_name: 'López',
            email: 'maria@example.com',
            password: 'password',
            phone_number: '0987654321',
            status: UserStatus::ACTIVO,
            roles: [new Role(3, UserRoles::PARENT->value)],
            id: 2
        );

        $this->repository->setNextFindByIdResult($user);

        $result = $this->repository->findById(2);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('María López', $result->fullName());
        $this->assertTrue($result->isParent());
    }

    #[Test]
    public function getUserWithStudentDetail_returns_user_with_student_detail(): void
    {
        $studentDetail = new StudentDetail(
            user_id: 1,
            id: 1,
            career_id: 1,
            n_control: '20240001',
            semestre: 3,
            group: 'A'
        );

        $user = new User(
            curp: 'STUDENT123456ABCD',
            name: 'Carlos',
            last_name: 'García',
            email: 'carlos@example.com',
            password: 'password',
            phone_number: '5551234567',
            status: UserStatus::ACTIVO,
            roles: [new Role(2, UserRoles::STUDENT->value)],
            id: 3,
            studentDetail: $studentDetail
        );

        $this->repository->setNextGetUserWithStudentDetailResult($user);

        $result = $this->repository->getUserWithStudentDetail(3);

        $this->assertInstanceOf(User::class, $result);
        $this->assertNotNull($result->studentDetail);
        $this->assertEquals('20240001', $result->studentDetail->n_control);
        $this->assertEquals(3, $result->studentDetail->semestre);
    }

    #[Test]
    public function getUserByStripeCustomer_returns_user_with_stripe_customer(): void
    {
        $user = new User(
            curp: 'STRIPE123456ABCDEF',
            name: 'Stripe',
            last_name: 'Customer',
            email: 'stripe@example.com',
            password: 'password',
            phone_number: '1234567890',
            status: UserStatus::ACTIVO,
            id: 4,
            stripe_customer_id: 'cus_test123456'
        );

        $this->repository->setNextGetUserByStripeCustomerResult($user);

        $result = $this->repository->getUserByStripeCustomer('cus_test123456');

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('cus_test123456', $result->stripe_customer_id);
        $this->assertEquals('Stripe Customer', $result->fullName());
    }

    #[Test]
    public function getUserIdsByControlNumbers_returns_user_id_list(): void
    {
        $dto = new UserIdListDTO([1, 2, 3, 4, 5]);
        $this->repository->setNextGetUserIdsByControlNumbersResult($dto);

        $result = $this->repository->getUserIdsByControlNumbers(['20240001', '20240002']);

        $this->assertInstanceOf(UserIdListDTO::class, $result);
        $this->assertIsArray($result->userIds);
        $this->assertCount(5, $result->userIds);
        $this->assertEquals([1, 2, 3, 4, 5], $result->userIds);
    }

    #[Test]
    public function countStudents_returns_student_count(): void
    {
        $this->repository->setNextCountStudentsResult(150);

        $result = $this->repository->countStudents(true);

        $this->assertIsInt($result);
        $this->assertEquals(150, $result);
    }

    #[Test]
    public function findBySearch_returns_user_when_found(): void
    {
        $user = new User(
            curp: 'SEARCH123456ABCD',
            name: 'Search',
            last_name: 'Test',
            email: 'search@example.com',
            password: 'password',
            phone_number: '5551112233',
            status: UserStatus::ACTIVO,
            id: 5
        );

        $this->repository->setNextFindBySearchResult($user);

        $result = $this->repository->findBySearch('SEARCH123456ABCD');

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('SEARCH123456ABCD', $result->curp);
        $this->assertEquals(5, $result->id);
    }

    #[Test]
    public function getRecipients_returns_array_of_recipients(): void
    {
        $recipients = [
            new UserRecipientDTO(1, 'Juan Pérez', 'juan@example.com'),
            new UserRecipientDTO(2, 'María López', 'maria@example.com'),
            new UserRecipientDTO(3, 'Carlos García', 'carlos@example.com'),
        ];

        $this->repository->setNextGetRecipientsResult($recipients);

        $result = $this->repository->getRecipients($this->testPaymentConcept, PaymentConceptAppliesTo::TODOS->value);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(UserRecipientDTO::class, $result);
        $this->assertEquals('Juan Pérez', $result[0]->fullName);
        $this->assertEquals('juan@example.com', $result[0]->email);
    }

    #[Test]
    public function getRecipientsIds_returns_array_of_ids(): void
    {
        $ids = [1, 2, 3, 4, 5];
        $this->repository->setNextGetRecipientsIdsResult($ids);

        $result = $this->repository->getRecipientsIds($this->testPaymentConcept, PaymentConceptAppliesTo::CARRERA->value);

        $this->assertIsArray($result);
        $this->assertEquals([1, 2, 3, 4, 5], $result);
    }

    #[Test]
    public function getRecipientsFromIds_returns_recipients_for_ids(): void
    {
        $recipients = [
            new UserRecipientDTO(1, 'Usuario Uno', 'uno@example.com'),
            new UserRecipientDTO(2, 'Usuario Dos', 'dos@example.com'),
        ];

        $this->repository->setNextGetRecipientsFromIdsResult($recipients);

        $result = $this->repository->getRecipientsFromIds([1, 2]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Usuario Uno', $result[0]->fullName);
    }

    #[Test]
    public function hasAnyRecipient_returns_boolean(): void
    {
        $this->repository->setNextHasAnyRecipientResult(true);
        $result1 = $this->repository->hasAnyRecipient($this->testPaymentConcept, PaymentConceptAppliesTo::TODOS->value);
        $this->assertTrue($result1);

        $this->repository->setNextHasAnyRecipientResult(false);
        $result2 = $this->repository->hasAnyRecipient($this->testPaymentConcept, PaymentConceptAppliesTo::SEMESTRE->value);
        $this->assertFalse($result2);
    }

    #[Test]
    public function hasRole_returns_boolean(): void
    {
        $this->repository->setNextHasRoleResult(true);
        $result = $this->repository->hasRole(1, UserRoles::STUDENT->value);
        $this->assertTrue($result);

        $this->repository->setNextHasRoleResult(false);
        $result = $this->repository->hasRole(1, UserRoles::ADMIN->value);
        $this->assertFalse($result);
    }

    #[Test]
    public function getStudentsWithPendingSummary_returns_summary_array(): void
    {
        $summaries = [
            new UserWithPendingSumamaryResponse(
                1,
                'Juan Pérez',
                ['student'],
                5,
                'Ingeniería en Sistemas',
                3,
                1,
                '4500.00',
                '7500.00',
                '500.00',
                2
            ),
            new UserWithPendingSumamaryResponse(
                2,
                'María López',
                ['student'],
                3,
                'Administración',
                2,
                0,
                '2000.00',
                '5000.00',
                '0.00',
                3
            ),
        ];

        $this->repository->setNextGetStudentsWithPendingSummaryResult($summaries);

        $result = $this->repository->getStudentsWithPendingSummary([1, 2]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(UserWithPendingSumamaryResponse::class, $result);
        $this->assertEquals('Juan Pérez', $result[0]->fullName);
        $this->assertEquals('Ingeniería en Sistemas', $result[0]->career_name);
        $this->assertEquals(3, $result[0]->num_pending);
    }

    #[Test]
    public function findActiveStudents_returns_paginated_students(): void
    {
        $items = [
            (object) ['id' => 1, 'name' => 'Juan', 'last_name' => 'Pérez', 'email' => 'juan@example.com'],
            (object) ['id' => 2, 'name' => 'María', 'last_name' => 'López', 'email' => 'maria@example.com'],
        ];

        $paginator = new LengthAwarePaginator($items, 50, 10, 1);
        $this->repository->setNextFindActiveStudentsResult($paginator);

        $result = $this->repository->findActiveStudents(null, 10, 1);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(2, $result->items());
        $this->assertEquals(50, $result->total());
        $this->assertEquals('Juan', $result->items()[0]->name);
    }

    #[Test]
    public function findAllUsers_returns_paginated_users(): void
    {
        $items = [
            (object) [
                'id' => 1,
                'name' => 'Admin',
                'last_name' => 'User',
                'email' => 'admin@example.com',
                'curp' => 'ADMIN123456ABCDEF',
                'phone_number' => '1234567890',
                'address' => ['Calle 123'],
                'blood_type' => 'O+',
                'status' => 'activo',
                'roles' => ['admin'],
                'permissions' => ['all']
            ],
        ];

        $paginator = new LengthAwarePaginator($items, 100, 20, 1);
        $this->repository->setNextFindAllUsersResult($paginator);

        $result = $this->repository->findAllUsers(20, 1, UserStatus::ACTIVO);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertCount(1, $result->items());
        $this->assertEquals('Admin User', $result->items()[0]->name . ' ' . $result->items()[0]->last_name);
    }

    #[Test]
    public function findAuthUser_returns_user_auth_response(): void
    {
        $response = new UserAuthResponse(
            1,
            'AUTH123456ABCDEFG',
            'Auth',
            'User',
            'auth@example.com',
            '5551234567',
            'activo',
            '2024-01-01',
            '2024-01-01',
            '1990-01-01',
            'male',
            ['Calle Principal 123'],
            'A+',
            'cus_auth123',
            [
                'control_number' => '20240001',
                'semestre' => 4,
                'career' => 'Sistemas'
            ]
        );

        $this->repository->setNextFindAuthUserResult($response);

        $result = $this->repository->findAuthUser();

        $this->assertInstanceOf(UserAuthResponse::class, $result);
        $this->assertEquals('Auth User', $result->name . ' ' . $result->last_name);
        $this->assertEquals('auth@example.com', $result->email);
    }

    #[Test]
    public function findByIds_returns_collection_of_users(): void
    {
        $user1 = new User(
            curp: 'COLL1',
            name: 'Collection',
            last_name: 'One',
            email: 'one@example.com',
            password: 'password',
            phone_number: '1111111111',
            status: UserStatus::ACTIVO,
            id: 1
        );

        $user2 = new User(
            curp: 'COLL2',
            name: 'Collection',
            last_name: 'Two',
            email: 'two@example.com',
            password: 'password',
            phone_number: '2222222222',
            status: UserStatus::ACTIVO,
            id: 2
        );

        $collection = collect([$user1, $user2]);
        $this->repository->setNextFindByIdsResult($collection);

        $result = $this->repository->findByIds([1, 2]);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(User::class, $result);
        $this->assertEquals('Collection One', $result->first()->fullName());
    }

    #[Test]
    public function findUserRoles_returns_array_of_roles(): void
    {
        $roles = [
            (object) ['id' => 2, 'name' => 'student'],
            (object) ['id' => 3, 'name' => 'financial-staff'],
        ];

        $this->repository->setNextFindUserRolesResult($roles);

        $result = $this->repository->findUserRoles(1);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('student', $result[0]->name);
    }

    #[Test]
    public function getUsersByRoleCursor_returns_generator(): void
    {
        $user1 = new class {
            public $id = 1;
            public $name = 'Role User 1';
            public $last_name = 'Test';
            public $curp = 'ROLE1';
        };

        $user2 = new class {
            public $id = 2;
            public $name = 'Role User 2';
            public $last_name = 'Test';
            public $curp = 'ROLE2';
        };

        $generator = (function () use ($user1, $user2) {
            yield $user1;
            yield $user2;
        })();

        $this->repository->setNextGetUsersByRoleCursorResult($generator);

        $result = $this->repository->getUsersByRoleCursor(UserRoles::STUDENT->value);

        $this->assertInstanceOf(Generator::class, $result);

        $users = iterator_to_array($result);
        $this->assertCount(2, $users);
        $this->assertEquals('Role User 1', $users[0]->name);
    }

    #[Test]
    public function getUsersByCurpCursor_returns_generator(): void
    {
        $user1 = new class {
            public $id = 1;
            public $name = 'CURP User 1';
            public $last_name = 'Test';
            public $curp = 'CURP123456ABCDEFG0';
        };

        $generator = (function () use ($user1) {
            yield $user1;
        })();

        $this->repository->setNextGetUsersByCurpCursorResult($generator);

        $result = $this->repository->getUsersByCurpCursor(['CURP123456ABCDEFG0']);

        $this->assertInstanceOf(Generator::class, $result);

        $users = iterator_to_array($result);
        $this->assertCount(1, $users);
        $this->assertEquals('CURP User 1', $users[0]->name);
        $this->assertEquals('CURP123456ABCDEFG0', $users[0]->curp);
    }

    #[Test]
    public function userHasUnreadNotifications_returns_boolean(): void
    {
        $this->repository->setNextUserHasUnreadNotificationsResult(true);
        $result = $this->repository->userHasUnreadNotifications(1);
        $this->assertTrue($result);

        $this->repository->setNextUserHasUnreadNotificationsResult(false);
        $result = $this->repository->userHasUnreadNotifications(2);
        $this->assertFalse($result);
    }

    #[Test]
    public function user_entity_methods_work_correctly(): void
    {
        $role1 = new Role(2, UserRoles::STUDENT->value);
        $role2 = new Role(3, UserRoles::FINANCIAL_STAFF->value);

        $user = new User(
            curp: 'ENTITY123456ABCD',
            name: 'Entity',
            last_name: 'Test',
            email: 'entity@example.com',
            password: 'password',
            phone_number: '5556667777',
            status: UserStatus::ACTIVO,
            roles: [$role1, $role2],
            id: 10,
            birthdate: Carbon::parse('1995-05-15'),
            gender: UserGender::HOMBRE,
            address: ['Calle 123', 'Colonia Centro'],
            blood_type: UserBloodType::O_POSITIVE,
            stripe_customer_id: 'cus_entity123'
        );

        // Test entity methods
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isDeleted());
        $this->assertFalse($user->isDisable());
        $this->assertEquals('Entity Test', $user->fullName());

        // Test role methods
        $this->assertTrue($user->hasRole(UserRoles::STUDENT->value));
        $this->assertTrue($user->hasRole(UserRoles::FINANCIAL_STAFF->value));
        $this->assertFalse($user->hasRole(UserRoles::ADMIN->value));
        $this->assertTrue($user->hasAnyRole([UserRoles::STUDENT->value, UserRoles::ADMIN->value]));

        $roleNames = $user->getRoleNames();
        $this->assertContains(UserRoles::STUDENT->value, $roleNames);
        $this->assertContains(UserRoles::FINANCIAL_STAFF->value, $roleNames);

        $studentRole = $user->getRole(UserRoles::STUDENT->value);
        $this->assertNotNull($studentRole);
        $this->assertEquals(UserRoles::STUDENT->value, $studentRole->name);

        // Test student methods
        $this->assertTrue($user->isStudent());
        $this->assertFalse($user->isApplicant());
        $this->assertFalse($user->isParent());
        $this->assertTrue($user->isNewStudent()); // No tiene studentDetail

        // Test adding student detail
        $studentDetail = new StudentDetail(
            user_id: 10,
            id: 5,
            career_id: 1,
            n_control: '20240010',
            semestre: 6,
            group: 'B'
        );

        $user->setStudentDetail($studentDetail);
        $this->assertNotNull($user->studentDetail);
        $this->assertEquals('20240010', $user->studentDetail->n_control);
        $this->assertFalse($user->isNewStudent()); // Ahora tiene studentDetail
    }

    #[Test]
    public function user_status_enum_methods_work_correctly(): void
    {
        // Test allowed transitions
        $allowedFromActive = UserStatus::ACTIVO->allowedTransitions();
        $this->assertIsArray($allowedFromActive);
        $this->assertContains(UserStatus::BAJA, $allowedFromActive);
        $this->assertContains(UserStatus::BAJA_TEMPORAL, $allowedFromActive);
        $this->assertContains(UserStatus::ELIMINADO, $allowedFromActive);

        // Test can transition
        $this->assertTrue(UserStatus::ACTIVO->canTransitionTo(UserStatus::BAJA));
        $this->assertTrue(UserStatus::BAJA->canTransitionTo(UserStatus::ACTIVO));
        $this->assertFalse(UserStatus::ELIMINADO->canTransitionTo(UserStatus::BAJA));

        // Test is updatable
        $this->assertTrue(UserStatus::ACTIVO->isUpdatable());
        $this->assertFalse(UserStatus::ELIMINADO->isUpdatable());
    }

    #[Test]
    public function methods_have_correct_signatures(): void
    {
        $this->assertMethodParameterType('findUserByEmail', 'string');
        $this->assertMethodParameterType('findById', 'int');
        $this->assertMethodParameterType('getUserWithStudentDetail', 'int');
        $this->assertMethodParameterType('getUserByStripeCustomer', 'string');
        $this->assertMethodParameterType('getUserIdsByControlNumbers', 'array');
        $this->assertMethodParameterType('countStudents', 'bool');
        $this->assertMethodParameterType('hasRole', 'int');
        $this->assertMethodParameterType('hasRole', 'string', 1);
        $this->assertMethodParameterType('userHasUnreadNotifications', 'int');

        $this->assertMethodReturnType('findUserByEmail', User::class);
        $this->assertMethodReturnType('findById', User::class);
        $this->assertMethodReturnType('getUserWithStudentDetail', User::class);
        $this->assertMethodReturnType('getUserByStripeCustomer', User::class);
        $this->assertMethodReturnType('getUserIdsByControlNumbers', UserIdListDTO::class);
        $this->assertMethodReturnType('countStudents', 'int');
        $this->assertMethodReturnType('hasRole', 'bool');
        $this->assertMethodReturnType('findActiveStudents', LengthAwarePaginator::class);
        $this->assertMethodReturnType('getRecipients', 'array');
        $this->assertMethodReturnType('getRecipientsIds', 'array');
        $this->assertMethodReturnType('getRecipientsFromIds', 'array');
        $this->assertMethodReturnType('hasAnyRecipient', 'bool');
        $this->assertMethodReturnType('findAllUsers', LengthAwarePaginator::class);
        $this->assertMethodReturnType('findAuthUser', UserAuthResponse::class);
        $this->assertMethodReturnType('findByIds', Collection::class);
        $this->assertMethodReturnType('findUserRoles', 'array');
        $this->assertMethodReturnType('getUsersByRoleCursor', Generator::class);
        $this->assertMethodReturnType('getUsersByCurpCursor', Generator::class);
        $this->assertMethodReturnType('userHasUnreadNotifications', 'bool');
    }
}
