<?php

namespace App\Core\Domain\Enum\Cache;

enum StaffCacheSufix: string
{
    case CONCEPTS = 'concepts';
    case DASHBOARD = 'dashboard';
    case DEBTS = 'debts';
    case PAYMENTS = 'payments';
    case STUDENTS = 'students';
}