<?php

use App\Core\Domain\Enum\User\UserRoles;

return [
    'expiration_time_by_role' => [
        UserRoles::UNVERIFIED->value => 6 * 60,

        UserRoles::APPLICANT->value => 24 * 60,
        UserRoles::STUDENT->value   => 24 * 60,
        UserRoles::PARENT->value    => 24 * 60,

        UserRoles::FINANCIAL_STAFF->value => 12 * 60,

        UserRoles::ADMIN->value      => 8 * 60,
        UserRoles::SUPERVISOR->value => 8 * 60,
    ],
    'default_refresh_ttl' => 24 * 60,
];
