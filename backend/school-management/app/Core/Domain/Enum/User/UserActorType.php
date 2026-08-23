<?php

namespace App\Core\Domain\Enum\User;

enum UserActorType: string
{
    case ADMIN = 'admin';
    case USER = 'user';
    case SYSTEM = 'system';

}
