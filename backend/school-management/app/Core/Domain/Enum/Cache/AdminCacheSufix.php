<?php

namespace App\Core\Domain\Enum\Cache;

enum AdminCacheSufix : string
{
    case USERS = 'users';
    case ROLES = 'roles';
    case PERMISSIONS_BY_ROLE = 'permissions_by_role';
    case PAYMENT_EVENTS = 'payment_events';
    case EMAIL_EVENTS = 'email_events';
    case RECONCILE_EVENTS = 'reconcile_events';
}
