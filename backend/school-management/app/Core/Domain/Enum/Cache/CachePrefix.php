<?php

namespace App\Core\Domain\Enum\Cache;

enum CachePrefix : string
{
    case ADMIN = 'admin';
    case STUDENT = 'student';
    case STAFF = 'staff';
    case USER = 'user';
    case CAREERS = 'careers';
    case PARENT = 'parent';
}