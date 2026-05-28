<?php

use \App\Core\Domain\Enum\Payment\PaymentStatus;

return [
    'statuses' => [
        PaymentStatus::UNPAID->value => 'No pagado',
        PaymentStatus::PAID->value => 'Pagado',
        PaymentStatus::REQUIRES_ACTION->value => 'Requiere acción',
        PaymentStatus::DEFAULT->value => 'Pendiente',
        PaymentStatus::UNDERPAID->value => 'Pago parcial',
        PaymentStatus::OVERPAID->value => 'Sobrepago',
        PaymentStatus::FAILED->value => 'Fallido',
        PaymentStatus::SUCCEEDED->value => 'Completado',
    ],
];
