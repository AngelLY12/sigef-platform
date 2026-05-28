<?php

namespace Tests\Unit\Domain\Enum;

use App\Core\Domain\Enum\User\UserRoles;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\Domain\EnumTestCase;

class UserRolesTest extends EnumTestCase
{
    protected function enumClass(): string
    {
        return UserRoles::class;
    }

    #[Test]
    public function it_has_all_expected_cases(): void
    {
        $cases = $this->getAllCases();

        $this->assertCount(7, $cases, 'UserRoles debe tener 7 casos');

        $expectedValues = [
            'student',
            'financial-staff',
            'parent',
            'unverified',
            'admin',
            'supervisor',
            'applicant',
        ];

        foreach ($expectedValues as $expectedValue) {
            $this->assertContains(
                $expectedValue,
                $this->getAllValues(),
                "UserRoles debe incluir '{$expectedValue}'"
            );
        }
    }

    #[Test]
    public function it_returns_all_values(): void
    {
        $values = UserRoles::values();

        $this->assertIsArray($values);
        $this->assertCount(7, $values);
        $this->assertContains('student', $values);
        $this->assertContains('financial-staff', $values);
        $this->assertContains('parent', $values);
        $this->assertContains('unverified', $values);
        $this->assertContains('admin', $values);
        $this->assertContains('supervisor', $values);
        $this->assertContains('applicant', $values);
    }

    #[Test]
    public function it_returns_correct_student_roles(): void
    {
        $students = UserRoles::students();

        $this->assertIsArray($students);
        $this->assertCount(2, $students);
        $this->assertContains(UserRoles::STUDENT, $students);
        $this->assertContains(UserRoles::APPLICANT, $students);

        $this->assertNotContains(UserRoles::FINANCIAL_STAFF, $students);
        $this->assertNotContains(UserRoles::PARENT, $students);
        $this->assertNotContains(UserRoles::UNVERIFIED, $students);
        $this->assertNotContains(UserRoles::ADMIN, $students);
        $this->assertNotContains(UserRoles::SUPERVISOR, $students);
    }

    #[Test]
    public function it_returns_correct_administration_roles(): void
    {
        $adminRoles = UserRoles::administrationRoles();

        $this->assertIsArray($adminRoles);
        $this->assertCount(2, $adminRoles);
        $this->assertContains('admin', $adminRoles);
        $this->assertContains('supervisor', $adminRoles);

        foreach ($adminRoles as $role) {
            $this->assertIsString($role);
        }

        $this->assertNotContains('student', $adminRoles);
        $this->assertNotContains('financial-staff', $adminRoles);
        $this->assertNotContains('parent', $adminRoles);
        $this->assertNotContains('unverified', $adminRoles);
        $this->assertNotContains('applicant', $adminRoles);
    }

    #[Test]
    public function values_method_returns_strings_not_objects(): void
    {
        $values = UserRoles::values();

        foreach ($values as $value) {
            $this->assertIsString($value);
            $this->assertNotInstanceOf(UserRoles::class, $value);
        }
    }

    #[Test]
    public function students_method_returns_enum_objects(): void
    {
        $students = UserRoles::students();

        foreach ($students as $student) {
            $this->assertInstanceOf(UserRoles::class, $student);
        }
    }

    #[Test]
    public function administration_roles_returns_values_not_objects(): void
    {
        $adminRoles = UserRoles::administrationRoles();

        foreach ($adminRoles as $role) {
            $this->assertIsString($role);
            $this->assertNotInstanceOf(UserRoles::class, $role);
        }
    }

    #[Test]
    public function roles_have_correct_hierarchy(): void
    {
        $this->assertEquals('admin', UserRoles::ADMIN->value);
        $this->assertEquals('supervisor', UserRoles::SUPERVISOR->value);

        $this->assertContains(UserRoles::ADMIN->value, UserRoles::administrationRoles());
        $this->assertContains(UserRoles::SUPERVISOR->value, UserRoles::administrationRoles());
    }

    #[Test]
    public function applicant_is_considered_student(): void
    {
        $students = UserRoles::students();

        $this->assertContains(UserRoles::APPLICANT, $students);
        $this->assertEquals('applicant', UserRoles::APPLICANT->value);

        $this->assertNotContains('applicant', UserRoles::administrationRoles());
    }

    #[Test]
    public function financial_staff_is_not_administration(): void
    {
        $this->assertNotContains('financial-staff', UserRoles::administrationRoles());
        $this->assertNotContains(UserRoles::FINANCIAL_STAFF, UserRoles::students());
    }

    #[Test]
    public function unverified_is_special_role(): void
    {
        $this->assertNotContains('unverified', UserRoles::administrationRoles());
        $this->assertNotContains(UserRoles::UNVERIFIED, UserRoles::students());

        $this->assertContains('unverified', UserRoles::values());
    }

    #[Test]
    public function parent_role_is_standalone(): void
    {
        $this->assertNotContains('parent', UserRoles::administrationRoles());
        $this->assertNotContains(UserRoles::PARENT, UserRoles::students());

        $this->assertContains('parent', UserRoles::values());
    }

    #[Test]
    public function all_roles_are_accounted_for(): void
    {
        $allRoles = UserRoles::cases();
        $groupedRoles = array_merge(
            UserRoles::students(),
            array_map(fn($value) => UserRoles::from($value), UserRoles::administrationRoles())
        );

        $uniqueGrouped = [];
        foreach ($groupedRoles as $role) {
            $uniqueGrouped[$role->value] = true;
        }

        $this->assertLessThan(count($allRoles), count($uniqueGrouped));
    }

    #[Test]
    public function it_provides_consistent_naming(): void
    {
        $this->assertEquals('STUDENT', UserRoles::STUDENT->name);
        $this->assertEquals('student', UserRoles::STUDENT->value);

        $this->assertEquals('FINANCIAL_STAFF', UserRoles::FINANCIAL_STAFF->name);
        $this->assertEquals('financial-staff', UserRoles::FINANCIAL_STAFF->value);

        $this->assertEquals('APPLICANT', UserRoles::APPLICANT->name);
        $this->assertEquals('applicant', UserRoles::APPLICANT->value);
    }

    #[Test]
    public function it_can_be_used_in_match_statements(): void
    {
        $role = UserRoles::ADMIN;

        $result = match($role) {
            UserRoles::STUDENT => 'student',
            UserRoles::FINANCIAL_STAFF => 'financial',
            UserRoles::PARENT => 'parent',
            UserRoles::UNVERIFIED => 'unverified',
            UserRoles::ADMIN => 'administrator',
            UserRoles::SUPERVISOR => 'supervisor',
            UserRoles::APPLICANT => 'applicant',
        };

        $this->assertEquals('administrator', $result);
    }

    #[Test]
    public function method_return_types_are_consistent(): void
    {
        $values = UserRoles::values();
        $this->assertIsArray($values);
        $this->assertContainsOnly('string', $values);

        $students = UserRoles::students();
        $this->assertIsArray($students);
        foreach ($students as $student) {
            $this->assertInstanceOf(UserRoles::class, $student);
        }

        $adminRoles = UserRoles::administrationRoles();
        $this->assertIsArray($adminRoles);
        $this->assertContainsOnly('string', $adminRoles);
    }

    #[Test]
    public function role_groups_are_mutually_exclusive(): void
    {
        $students = UserRoles::students();
        $adminValues = UserRoles::administrationRoles();

        $studentValues = array_map(fn($role) => $role->value, $students);

        $overlap = array_intersect($studentValues, $adminValues);
        $this->assertEmpty($overlap, 'No debería haber roles que sean tanto estudiantes como administración');
    }

    #[Test]
    public function special_characters_in_values(): void
    {
        $this->assertEquals('financial-staff', UserRoles::FINANCIAL_STAFF->value);

        $fromValue = UserRoles::from('financial-staff');
        $this->assertEquals(UserRoles::FINANCIAL_STAFF, $fromValue);
    }

    #[Test]
    public function business_logic_validation(): void
    {
        $this->assertContains(UserRoles::APPLICANT, UserRoles::students());

        $this->assertContains('admin', UserRoles::administrationRoles());
        $this->assertContains('supervisor', UserRoles::administrationRoles());

        $this->assertEquals('unverified', UserRoles::UNVERIFIED->value);

        $allValues = UserRoles::values();
        $expectedCount = count(UserRoles::cases());
        $this->assertCount($expectedCount, $allValues);
    }

}
