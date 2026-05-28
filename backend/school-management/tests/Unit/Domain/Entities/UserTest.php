<?php

namespace Tests\Unit\Domain\Entities;

use Tests\Unit\Domain\BaseDomainTestCase;
use App\Core\Domain\Entities\User;
use App\Core\Domain\Entities\Role;
use App\Core\Domain\Entities\StudentDetail;
use App\Core\Domain\Enum\User\UserGender;
use App\Core\Domain\Enum\User\UserBloodType;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Domain\Enum\User\UserRoles;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class UserTest extends BaseDomainTestCase
{
    private function createValidUser(array $overrides = []): User
    {

        $defaultData = [
            'id' => null,
            'name' => 'Juan',
            'last_name' => 'Pérez',
            'email' => 'juan.perez@example.com',
            'password' => 'SecurePass123!',
            'phone_number' => '+521234567890',
            'birthdate' => Carbon::parse('1990-01-01'),
            'gender' => UserGender::HOMBRE,
            'curp' => 'PEMJ900101HDFLRN01',
            'address' => ['street' => 'Calle Falsa', 'city' => 'CDMX'],
            'stripe_customer_id' => 'cus_123456',
            'blood_type' => UserBloodType::O_POSITIVE,
            'registration_date' => Carbon::now(),
            'status' => UserStatus::ACTIVO,
            'studentDetail' => null,
            'roles' => [],
            'emailVerified' => false
        ];

        $data = array_merge($defaultData, $overrides);

        return new User(
            $data['curp'],
            $data['name'],
            $data['last_name'],
            $data['email'],
            $data['password'],
            $data['phone_number'],
            $data['status'],
            $data['registration_date'],
            $data['emailVerified'],
            $data['roles'],
            $data['id'],
            $data['birthdate'],
            $data['gender'],
            $data['address'],
            $data['blood_type'],
            $data['stripe_customer_id'],
            $data['studentDetail'],

        );
    }

    #[Test]
    public function it_can_be_instantiated()
    {
        $user = $this->createValidUser();

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Juan', $user->name);
        $this->assertEquals('Pérez', $user->last_name);
        $this->assertEquals('juan.perez@example.com', $user->email);
    }

    #[Test]
    public function it_has_all_required_properties()
    {
        $user = $this->createValidUser();

        $requiredProperties = [
            'curp', 'name', 'last_name', 'email', 'password',
            'phone_number'
        ];

        foreach ($requiredProperties as $property) {
            $this->assertTrue(property_exists($user, $property));
        }
    }

    #[Test]
    public function it_returns_full_name()
    {
        $user = $this->createValidUser();

        $this->assertEquals('Juan Pérez', $user->fullName());

        // Test con diferentes nombres
        $user2 = $this->createValidUser(['name' => 'Ana', 'last_name' => 'García']);
        $this->assertEquals('Ana García', $user2->fullName());
    }

    #[Test]
    public function it_checks_status_correctly()
    {
        // Test ACTIVO
        $user = $this->createValidUser(['status' => UserStatus::ACTIVO]);
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isDeleted());
        $this->assertFalse($user->isDisable());

        // Test ELIMINADO
        $user = $this->createValidUser(['status' => UserStatus::ELIMINADO]);
        $this->assertFalse($user->isActive());
        $this->assertTrue($user->isDeleted());
        $this->assertFalse($user->isDisable());

        // Test BAJA
        $user = $this->createValidUser(['status' => UserStatus::BAJA]);
        $this->assertFalse($user->isActive());
        $this->assertFalse($user->isDeleted());
        $this->assertTrue($user->isDisable());
    }

    #[Test]
    public function it_can_have_student_detail()
    {
        $user = $this->createValidUser();

        $studentDetail = $this->createMock(StudentDetail::class);

        $user->setStudentDetail($studentDetail);

        $this->assertSame($studentDetail, $user->getStudentDetail());
    }

    #[Test]
    public function it_can_add_and_manage_roles()
    {
        $user = $this->createValidUser();

        $studentRole = new Role(1, UserRoles::STUDENT->value, 'Estudiante');
        $parentRole = new Role(2, UserRoles::PARENT->value, 'Padre/Tutor');

        // Agregar roles
        $user->addRole($studentRole);
        $user->addRole($parentRole);

        // No debería duplicar roles
        $user->addRole($studentRole);

        $this->assertCount(2, $user->getRoles());
        $this->assertTrue($user->hasRole(UserRoles::STUDENT->value));
        $this->assertTrue($user->hasRole(UserRoles::PARENT->value));

        // Verificar hasAnyRole
        $this->assertTrue($user->hasAnyRole([UserRoles::STUDENT->value, UserRoles::APPLICANT->value]));

        // Verificar getRole
        $this->assertSame($studentRole, $user->getRole(UserRoles::STUDENT->value));
        $this->assertNull($user->getRole(UserRoles::APPLICANT->value));

        // Verificar getRoleNames
        $this->assertEquals(
            [UserRoles::STUDENT->value, UserRoles::PARENT->value],
            $user->getRoleNames()
        );

        // Verificar hasNoRole
        $userWithoutRoles = $this->createValidUser();
        $this->assertTrue($userWithoutRoles->hasNoRole());
        $this->assertFalse($user->hasNoRole());
    }

    #[Test]
    public function it_checks_role_based_methods()
    {
        $studentRole = new Role(1, UserRoles::STUDENT->value, 'Estudiante');
        $applicantRole = new Role(3, UserRoles::APPLICANT->value, 'Aspirante');
        $parentRole = new Role(2, UserRoles::PARENT->value, 'Padre/Tutor');

        // Test STUDENT
        $student = $this->createValidUser(['roles' => [$studentRole]]);
        $this->assertTrue($student->isStudent());
        $this->assertFalse($student->isApplicant());
        $this->assertFalse($student->isParent());

        // Test APPLICANT
        $applicant = $this->createValidUser(['roles' => [$applicantRole]]);
        $this->assertTrue($applicant->isApplicant());
        $this->assertFalse($applicant->isStudent());
        $this->assertFalse($applicant->isParent());

        // Test PARENT
        $parent = $this->createValidUser(['roles' => [$parentRole]]);
        $this->assertTrue($parent->isParent());
        $this->assertFalse($parent->isStudent());
        $this->assertFalse($parent->isApplicant());
    }

    #[Test]
    public function it_checks_if_user_is_new_student()
    {
        $studentRole = new Role(1, UserRoles::STUDENT->value, 'Estudiante');

        // Test NEW STUDENT (student sin studentDetail)
        $newStudent = $this->createValidUser([
            'roles' => [$studentRole],
            'studentDetail' => null
        ]);
        $this->assertTrue($newStudent->isNewStudent());

        // Test STUDENT con detail
        $studentDetail = $this->createMock(StudentDetail::class);
        $studentWithDetail = $this->createValidUser([
            'roles' => [$studentRole],
            'studentDetail' => $studentDetail
        ]);
        $this->assertFalse($studentWithDetail->isNewStudent());
    }

    #[Test]
    public function it_handles_optional_fields_correctly()
    {
        $minimalUser = new User(
            'GARA900101MDFLRN02',
            'Ana',
            'García',
            'ana@example.com',
            'Password123!',
            '+521234567891',
        );

        $this->assertInstanceOf(User::class, $minimalUser);
        $this->assertEquals('Ana', $minimalUser->name);
        $this->assertNull($minimalUser->birthdate);
        $this->assertNull($minimalUser->gender);
        $this->assertEmpty($minimalUser->roles);
        $this->assertFalse($minimalUser->emailVerified);
    }

    #[Test]
    public function it_can_be_converted_to_json()
    {
        $user = $this->createValidUser(['id' => 1]);

        $json = json_encode($user);

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertArrayHasKey('id', $decoded);
        $this->assertEquals(1, $decoded['id']);
        $this->assertEquals('Juan', $decoded['name']);
    }

    #[Test]
    public function it_handles_multiple_roles()
    {
        $studentRole = new Role(1, UserRoles::STUDENT->value, 'Estudiante');
        $parentRole = new Role(2, UserRoles::PARENT->value, 'Padre/Tutor');

        $user = $this->createValidUser(['roles' => [$studentRole, $parentRole]]);

        $this->assertTrue($user->isStudent());
        $this->assertTrue($user->isParent());
        $this->assertFalse($user->isApplicant());
        $this->assertCount(2, $user->getRoles());
    }
}
