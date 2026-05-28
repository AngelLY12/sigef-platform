<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pago estudiantil (lectura / vistas)
    |--------------------------------------------------------------------------
    */
    'view.own.pending.concepts.summary' => [
        'label' => 'Ver resumen de conceptos pendientes propios',
        'group' => 'Pagos',
    ],
    'view.own.paid.concepts.summary' => [
        'label' => 'Ver resumen de conceptos pagados propios',
        'group' => 'Pagos',
    ],
    'view.own.overdue.concepts.summary' => [
        'label' => 'Ver resumen de conceptos vencidos propios',
        'group' => 'Pagos',
    ],
    'view.payments.summary' => [
        'label' => 'Ver resumen de pagos',
        'group' => 'Pagos',
    ],
    'view.cards' => [
        'label' => 'Ver tarjetas registradas',
        'group' => 'Pagos',
    ],
    'view.payments.history' => [
        'label' => 'Ver historial de pagos',
        'group' => 'Pagos',
    ],
    'view.pending.concepts' => [
        'label' => 'Ver conceptos pendientes',
        'group' => 'Pagos',
    ],
    'view.overdue.concepts' => [
        'label' => 'Ver conceptos vencidos',
        'group' => 'Pagos',
    ],
    'view.receipt' => [
        'label' => 'Ver recibo del pago',
        'group' => 'Pagos',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pago estudiantil (acciones)
    |--------------------------------------------------------------------------
    */
    'create.setup' => [
        'label' => 'Configurar método de pago',
        'group' => 'Pagos',
    ],
    'delete.card' => [
        'label' => 'Eliminar método de pago',
        'group' => 'Pagos',
    ],
    'create.payment' => [
        'label' => 'Realizar pago',
        'group' => 'Pagos',
    ],

    /*
    |--------------------------------------------------------------------------
    | Financiero / staff (lectura)
    |--------------------------------------------------------------------------
    */
    'view.all.pending.concepts.summary' => [
        'label' => 'Ver resumen de conceptos pendientes de todos los estudiantes',
        'group' => 'Finanzas',
    ],
    'view.all.students.summary' => [
        'label' => 'Ver resumen de estudiantes',
        'group' => 'Finanzas',
    ],
    'view.all.paid.concepts.summary' => [
        'label' => 'Ver resumen de conceptos pagados',
        'group' => 'Finanzas',
    ],
    'view.concepts.summary' => [
        'label' => 'Ver resumen de conceptos',
        'group' => 'Finanzas',
    ],
    'view.concepts' => [
        'label' => 'Ver conceptos',
        'group' => 'Finanzas',
    ],
    'view.debts' => [
        'label' => 'Ver deudas',
        'group' => 'Finanzas',
    ],
    'view.payments' => [
        'label' => 'Ver pagos',
        'group' => 'Finanzas',
    ],

    /*
    |--------------------------------------------------------------------------
    | Financiero / staff (acciones)
    |--------------------------------------------------------------------------
    */
    'create.concepts' => [
        'label' => 'Crear conceptos de pago',
        'group' => 'Finanzas',
    ],
    'update.concepts' => [
        'label' => 'Actualizar conceptos de pago',
        'group' => 'Finanzas',
    ],
    'finalize.concepts' => [
        'label' => 'Finalizar conceptos de pago',
        'group' => 'Finanzas',
    ],
    'disable.concepts' => [
        'label' => 'Deshabilitar conceptos de pago',
        'group' => 'Finanzas',
    ],
    'eliminate.concepts' => [
        'label' => 'Eliminar conceptos de pago',
        'group' => 'Finanzas',
    ],
    'activate.concepts' => [
        'label' => 'Activar conceptos de pago',
        'group' => 'Finanzas',
    ],
    'validate.debt' => [
        'label' => 'Validar deuda',
        'group' => 'Finanzas',
    ],
    'view.payments.student.summary' => [
        'label' => 'Ver el resumen financiero de los estudiantes',
        'group' => 'Finanzas',
    ],
    'view.stripe.payments' => [
        'label' => 'Ver pagos en Stripe',
        'group' => 'Finanzas',
    ],
    'create.payout' => [
        'label' => 'Crear liquidación de pagos',
        'group' => 'Finanzas',
    ],

    /*
    |--------------------------------------------------------------------------
    | Globales
    |--------------------------------------------------------------------------
    */
    'refresh.all.dashboard' => [
        'label' => 'Actualizar tablero de pagos',
        'group' => 'Global',
    ],

    /*
    |--------------------------------------------------------------------------
    | Administración
    |--------------------------------------------------------------------------
    */
    'attach.student' => [
        'label' => 'Asignar detalles académicos',
        'group' => 'Administración',
    ],
    'import.users' => [
        'label' => 'Importar usuarios',
        'group' => 'Administración',
    ],
    'sync.permissions' => [
        'label' => 'Sincronizar permisos',
        'group' => 'Administración',
    ],
    'view.users' => [
        'label' => 'Ver usuarios',
        'group' => 'Administración',
    ],
    'sync.roles' => [
        'label' => 'Sincronizar roles',
        'group' => 'Administración',
    ],
    'activate.users' => [
        'label' => 'Activar usuarios',
        'group' => 'Administración',
    ],
    'disable.users' => [
        'label' => 'Deshabilitar usuarios',
        'group' => 'Administración',
    ],
    'delete.users' => [
        'label' => 'Eliminar usuarios',
        'group' => 'Administración',
    ],
    'view.permissions' => [
        'label' => 'Ver permisos',
        'group' => 'Administración',
    ],
    'view.roles' => [
        'label' => 'Ver roles',
        'group' => 'Administración',
    ],
    'create.user' => [
        'label' => 'Crear usuario',
        'group' => 'Administración',
    ],
    'view.student' => [
        'label' => 'Ver detalles académicos',
        'group' => 'Administración',
    ],
    'update.student' => [
        'label' => 'Actualizar detalles académicos',
        'group' => 'Administración',
    ],
    'promote.student' => [
        'label' => 'Incrementar semestres',
        'group' => 'Administración',
    ],
];
