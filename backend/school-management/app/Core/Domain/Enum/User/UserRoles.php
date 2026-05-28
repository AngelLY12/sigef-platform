<?php

namespace App\Core\Domain\Enum\User;

enum UserRoles: string
{
    case STUDENT = 'student';
    case FINANCIAL_STAFF = 'financial-staff';
    case PARENT = 'parent';
    case UNVERIFIED = 'unverified';
    case ADMIN = 'admin';
    case SUPERVISOR = 'supervisor';
    case APPLICANT = 'applicant';

    public static function values(): array
    {
        return array_map(fn($role) => $role->value, self::cases());
    }

    public static function students(): array
    {
        return [
            self::STUDENT,
            self::APPLICANT,
        ];
    }

    public static function paymentContext(): array
    {
        return [
            self::STUDENT->value,
            self::APPLICANT->value,
            self::PARENT->value,
        ];
    }

    public static function financialStaffContext(): array
    {
        return [
            self::FINANCIAL_STAFF->value,
        ];
    }

    public static function administrationContext(): array
    {
        return [
            self::ADMIN->value,
            self::SUPERVISOR->value,
        ];
    }

    public static function globalPaymentContext(): array
    {
        return [
            self::STUDENT->value,
            self::APPLICANT->value,
            self::PARENT->value,
            self::FINANCIAL_STAFF->value,
        ];
    }

    public static function administrationRoles(): array
    {
        return [self::ADMIN->value, self::SUPERVISOR->value];
    }

}
