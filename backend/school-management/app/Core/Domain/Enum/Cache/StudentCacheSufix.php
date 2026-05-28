<?php

namespace App\Core\Domain\Enum\Cache;

enum StudentCacheSufix: string
{
    case CARDS = 'cards';
    case DASHBOARD_USER = 'dashboard-user';
    case HISTORY = 'history';
    case PENDING = 'pending';
}