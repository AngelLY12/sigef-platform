<?php

use App\Core\Domain\Enum\User\UserRoles;

return [
    'amount' => [
        'min' => '10.00',
        'max' => env('AMOUNT_MAX', '25000.00'),
        'notifications' => [
            'enabled' => (bool) env('AMOUNT_NOTIFY_ENABLED', true),
            'threshold' => env('AMOUNT_NOTIFY_THRESHOLD', '2500.00'),
            'recipient_roles' => UserRoles::administrationRoles(),
            'channels' => ['mail'],
            'mail' => [
                'title' => 'Alerta: Monto excede límite',
                'intro' => 'Se ha detectado un concepto que excede el límite establecido, se debe verificar'
            ],
        ],
    ],
];
