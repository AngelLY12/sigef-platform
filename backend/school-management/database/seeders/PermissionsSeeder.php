<?php

namespace Database\Seeders;

use App\Core\Domain\Enum\User\UserRoles;
use App\Models\PermissionContext;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionContexts = [
            // Permisos de pago estudiantil (compartidos)
            'view.own.pending.concepts.summary' => ['payment' => UserRoles::paymentContext()],
            'view.own.paid.concepts.summary' => ['payment' => UserRoles::paymentContext()],
            'view.own.overdue.concepts.summary' => ['payment' => UserRoles::paymentContext()],
            'view.payments.summary' => ['payment' => UserRoles::paymentContext()],
            'view.cards' => ['payment' => UserRoles::paymentContext()],
            'view.payments.history' => ['payment' => UserRoles::paymentContext()],
            'view.pending.concepts' => ['payment' => UserRoles::paymentContext()],
            'view.overdue.concepts' => ['payment' => UserRoles::paymentContext()],
            'view.receipt' => ['payment' => UserRoles::paymentContext()],

            // Permisos de acción (model) para pagos
            'create.setup' => ['payment' => UserRoles::paymentContext()],
            'delete.card' => ['payment' => UserRoles::paymentContext()],
            'create.payment' => ['payment' => UserRoles::paymentContext()],

            // Permisos financieros (staff)
            'view.all.pending.concepts.summary' => ['financial-admin' => UserRoles::financialStaffContext()],
            'view.all.students.summary' => ['financial-admin' => UserRoles::financialStaffContext()],
            'view.all.paid.concepts.summary' => ['financial-admin' => UserRoles::financialStaffContext()],
            'view.concepts.summary' => ['financial-admin' => UserRoles::financialStaffContext()],
            'view.concepts' => ['financial-admin' => UserRoles::financialStaffContext()],
            'view.debts' => ['financial-admin' => UserRoles::financialStaffContext()],
            'view.payments' => ['financial-admin' => UserRoles::financialStaffContext()],


            // Acciones financieras (staff)
            'create.concepts' => ['financial-admin' => UserRoles::financialStaffContext()],
            'update.concepts' => ['financial-admin' => UserRoles::financialStaffContext()],
            'finalize.concepts' => ['financial-admin' => UserRoles::financialStaffContext()],
            'disable.concepts' => ['financial-admin' => UserRoles::financialStaffContext()],
            'eliminate.concepts' => ['financial-admin' => UserRoles::financialStaffContext()],
            'activate.concepts' => ['financial-admin' => UserRoles::financialStaffContext()],
            'validate.debt' => ['financial-admin' => UserRoles::financialStaffContext()],
            'view.payments.student.summary' => ['financial-admin' => UserRoles::financialStaffContext()],
            'view.stripe.payments' => ['financial-admin' => UserRoles::financialStaffContext()],
            'create.payout' => ['financial-admin' => UserRoles::financialStaffContext()],

            // Permisos globales de pago
            'refresh.all.dashboard' => ['global-payment' => UserRoles::globalPaymentContext()],

            // Permisos administrativos
            'attach.student' => ['administration' => UserRoles::administrationContext()],
            'import.users' => ['administration' => UserRoles::administrationContext()],
            'sync.permissions' => ['administration' => UserRoles::administrationContext()],
            'view.users' => ['administration' => UserRoles::administrationContext()],
            'sync.roles' => ['administration' => UserRoles::administrationContext()],
            'activate.users' => ['administration' => UserRoles::administrationContext()],
            'disable.users' => ['administration' => UserRoles::administrationContext()],
            'delete.users' => ['administration' => UserRoles::administrationContext()],
            'view.permissions' => ['administration' => UserRoles::administrationContext()],
            'view.roles' => ['administration' => UserRoles::administrationContext()],
            'create.user' => ['administration' => UserRoles::administrationContext()],
            'view.student' => ['administration' => UserRoles::administrationContext()],
            'update.student' => ['administration' => UserRoles::administrationContext()],
            'promote.student' => ['administration' => UserRoles::administrationContext()],
        ];

        /**
         * Mapa de tipos de permisos
         */
        $permissionTypes = [
            // Pago estudiantil
            'view.own.pending.concepts.summary' => 'role',
            'view.own.paid.concepts.summary' => 'role',
            'view.own.overdue.concepts.summary' => 'role',
            'view.payments.summary' => 'role',
            'view.cards' => 'role',
            'view.payments.history' => 'role',
            'view.pending.concepts' => 'role',
            'view.overdue.concepts' => 'role',
            'view.receipt' => 'role',
            'create.setup' => 'model',
            'delete.card' => 'model',
            'create.payment' => 'model',

            // Financiero
            'view.all.pending.concepts.summary' => 'role',
            'view.all.students.summary' => 'role',
            'view.all.paid.concepts.summary' => 'role',
            'view.concepts.summary' => 'role',
            'view.concepts' => 'role',
            'view.debts' => 'role',
            'view.payments' => 'role',
            'create.concepts' => 'model',
            'update.concepts' => 'model',
            'finalize.concepts' => 'model',
            'disable.concepts' => 'model',
            'eliminate.concepts' => 'model',
            'activate.concepts' => 'model',
            'validate.debt' => 'model',
            'view.payments.student.summary' => 'model',
            'view.stripe.payments' => 'model',
            'create.payout' => 'model',

            // Global
            'refresh.all.dashboard' => 'role',

            // Administración
            'attach.student' => 'model',
            'import.users' => 'model',
            'sync.permissions' => 'model',
            'view.users' => 'model',
            'sync.roles' => 'model',
            'activate.users' => 'model',
            'disable.users' => 'model',
            'delete.users' => 'model',
            'view.permissions' => 'model',
            'view.roles' => 'model',
            'create.user' => 'model',
            'view.student' => 'model',
            'update.student' => 'model',
            'promote.student' => 'model',
        ];


        foreach ($permissionContexts as $permissionName => $contextMap) {
            $permission = Permission::updateOrCreate(
                ['name' => $permissionName, 'guard_name' => 'sanctum'],
                ['type' => $permissionTypes[$permissionName] ?? 'role']
            );

            foreach ($contextMap as $context => $roles) {
                foreach ($roles as $role) {
                    PermissionContext::updateOrCreate(
                        [
                            'permission_id' => $permission->id,
                            'context' => $context,
                            'target_role' => $role
                        ]
                    );
                }
            }
        }
    }
}
