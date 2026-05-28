<?php

namespace Database\Seeders;

use App\Core\Domain\Enum\User\UserRoles;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $createdRoles = [];

        foreach (UserRoles::values() as $roleName) {
            $attributes = ['name' => $roleName, 'guard_name' => 'sanctum'];

            if ($roleName === UserRoles::ADMIN->value) {
                $attributes['hidden'] = true;
            }

            $createdRoles[$roleName] = Role::updateOrCreate($attributes);
        }

        foreach ($createdRoles as $roleName => $role) {
            $query = Permission::whereHas('contexts', fn($q) => $q->where('target_role', $roleName));
            if ($roleName === UserRoles::ADMIN->value) {
                $query->where('type', 'model');
            } else {
                $query->where('type', 'role');
            }

            $permissionIds = $query->pluck('id')->toArray();


            if (!empty($permissionIds)) {
                $role->syncPermissions($permissionIds);
            }
        }
    }
}
