<?php

namespace App\Core\Domain\Enum\Cache;

enum AdminCacheSufix : string
{
    case USERS = 'users';
    case ROLES = 'roles';
    case PERMISSIONS_BY_ROLE = 'permissions_by_role';
}
