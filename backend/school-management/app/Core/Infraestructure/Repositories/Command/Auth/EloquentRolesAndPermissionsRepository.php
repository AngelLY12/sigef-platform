<?php

namespace App\Core\Infraestructure\Repositories\Command\Auth;

use App\Core\Application\DTO\Response\General\PermissionsUpdatedToUserResponse;
use App\Core\Application\DTO\Response\General\RolesUpdatedToUserResponse;
use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Repositories\Command\Auth\RolesAndPermissionsRepInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use App\Models\User as EloquentUser;
use Illuminate\Support\Collection;

class EloquentRolesAndPermissionsRepository implements RolesAndPermissionsRepInterface
{

    public function assignRoles(array $roleRows): void {
        if (!empty($roleRows)) {
            DB::table('model_has_roles')->insertOrIgnore($roleRows);
        }
    }


    public function givePermissionsByType(EloquentUser $user, string $targetRole, string $type = 'model'): void
    {
        $permissions = \App\Models\Permission::whereHas('contexts', fn($q) => $q->where('target_role', $targetRole))
            ->where('type', $type)
            ->pluck('name')
            ->toArray();

        $user->givePermissionTo($permissions);
    }

    public function removePermissions(array $userIds, array $permissionIds): int
    {
        if (empty($permissionIds) || empty($userIds)) {
            return 0;
        }

        return DB::table('model_has_permissions')
            ->whereIn('model_id', $userIds)
            ->whereIn('permission_id', $permissionIds)
            ->where('model_type', EloquentUser::class)
            ->delete();
    }

    public function addPermissions(array $userIds, array $permissionIds): int
    {
        if (empty($permissionIds) || empty($userIds)) {
            return 0;
        }

        $rows = collect($userIds)->crossJoin($permissionIds)->map(fn($pair) => [
            'model_id' => $pair[0],
            'permission_id' => $pair[1],
            'model_type' => EloquentUser::class,
        ])->toArray();

        try {
            return DB::table('model_has_permissions')->insertOrIgnore($rows);
        } catch (\Exception $e) {
            return $this->insertPermissionsWithCount($userIds, $permissionIds);
        }
    }

    private function insertPermissionsWithCount(array $userIds, array $permissionIds): int
    {
        $insertados = 0;
        foreach ($userIds as $userId) {
            foreach ($permissionIds as $permissionId) {
                try {
                    $insertado = DB::table('model_has_permissions')->insertOrIgnore([
                        'model_id' => $userId,
                        'permission_id' => $permissionId,
                        'model_type' => EloquentUser::class,
                    ]);
                    if ($insertado) {
                        $insertados++;
                    }
                } catch (\Exception $e) {
                }
            }
        }
        return $insertados;
    }

    public function getUsersWithPermissionChanges(
        array $userIds,
        array $permissionsToAddIds,
        array $permissionsToRemoveIds
    ): array {
        $result = [
            'affected' => [],
            'unchanged' => [],
        ];

        if (empty($permissionsToAddIds) && empty($permissionsToRemoveIds)) {
            $result['unchanged'] = $userIds;
            return $result;
        }

        $permsPorUsuario = DB::table('model_has_permissions')
            ->whereIn('model_id', $userIds)
            ->where('model_type', EloquentUser::class)
            ->select('model_id', DB::raw('GROUP_CONCAT(permission_id) as perms'))
            ->groupBy('model_id')
            ->pluck('perms', 'model_id');

        foreach ($userIds as $userId) {
            $permsStr = $permsPorUsuario[$userId] ?? '';
            $actuales = $permsStr ? explode(',', $permsStr) : [];
            $actualesMap = array_flip($actuales);

            $faltan = false;
            foreach ($permissionsToAddIds as $pid) {
                if (!isset($actualesMap[$pid])) {
                    $faltan = true;
                    break;
                }
            }

            $sobran = false;
            foreach ($permissionsToRemoveIds as $pid) {
                if (isset($actualesMap[$pid])) {
                    $sobran = true;
                    break;
                }
            }

            if ($faltan || $sobran) {
                $result['affected'][] = $userId;
            } else {
                $result['unchanged'][] = $userId;
            }
        }

        return $result;
    }

    public function syncRoles(Collection $users, array $rolesToAddIds, array $rolesToRemoveIds): array
    {
        $userIds = $users->pluck('id')->toArray();

        $usuariosConCambios = $this->getUsersWithRoleChanges(
            $userIds,
            $rolesToAddIds,
            $rolesToRemoveIds
        );

        $resultado = [
            'removed' => 0,
            'added' => 0,
            'users_affected' => $usuariosConCambios['affected'],
            'users_unchanged' => $usuariosConCambios['unchanged'],
        ];

        DB::transaction(function () use ($usuariosConCambios, $rolesToAddIds, $rolesToRemoveIds, &$resultado) {
            $userIds = $usuariosConCambios['affected'];
            if (empty($userIds)) {
                return;
            }
            if (!empty($rolesToRemoveIds)) {
                $resultado['removed'] = DB::table('model_has_roles')
                    ->whereIn('model_id', $userIds)
                    ->whereIn('role_id', $rolesToRemoveIds)
                    ->where('model_type', EloquentUser::class)
                    ->delete();
            }

            if (!empty($rolesToAddIds)) {
                $rolesExistentes = DB::table('model_has_roles')
                    ->whereIn('model_id', $userIds)
                    ->whereIn('role_id', $rolesToAddIds)
                    ->where('model_type', EloquentUser::class)
                    ->select('model_id', 'role_id')
                    ->get()
                    ->groupBy('model_id')
                    ->map(fn($items) => $items->pluck('role_id')->toArray())
                    ->toArray();

                $rows = [];
                foreach ($userIds as $userId) {
                    $rolesExistentesUsuario = $rolesExistentes[$userId] ?? [];
                    foreach ($rolesToAddIds as $roleId) {
                        if (!in_array($roleId, $rolesExistentesUsuario)) {
                            $rows[] = [
                                'role_id' => $roleId,
                                'model_type' => EloquentUser::class,
                                'model_id' => $userId,
                            ];
                        }
                    }
                }

                if (!empty($rows)) {
                    $resultado['added'] = count($rows);
                    DB::table('model_has_roles')->insertOrIgnore($rows);
                }
            }
        });

        return $resultado;
    }

    private function getUsersWithRoleChanges(array $userIds, array $rolesToAddIds, array $rolesToRemoveIds): array
    {
        $result = [
            'affected' => [],
            'unchanged' => [],
        ];
        if (empty($rolesToAddIds) && empty($rolesToRemoveIds)) {
            $result['unchanged'] = $userIds;
            return $result;
        }

        $rolesPorUsuario = DB::table('model_has_roles')
            ->whereIn('model_id', $userIds)
            ->where('model_type', EloquentUser::class)
            ->select('model_id', DB::raw('GROUP_CONCAT(role_id) as roles'))
            ->groupBy('model_id')
            ->pluck('roles', 'model_id');

        foreach ($userIds as $userId) {
            $rolesActualesStr = $rolesPorUsuario[$userId] ?? '';
            $rolesActuales = $rolesActualesStr ? explode(',', $rolesActualesStr) : [];
            $rolesActualesMap = array_flip($rolesActuales);

            $faltanRoles = false;
            foreach ($rolesToAddIds as $roleId) {
                if (!isset($rolesActualesMap[$roleId])) {
                    $faltanRoles = true;
                    break;
                }
            }

            $tienenRolesNoRemovidos = false;
            foreach ($rolesToRemoveIds as $roleId) {
                if (isset($rolesActualesMap[$roleId])) {
                    $tienenRolesNoRemovidos = true;
                    break;
                }
            }

            if ($faltanRoles || $tienenRolesNoRemovidos) {
                $result['affected'][] = $userId;
            }
            else{
                $result['unchanged'][] = $userId;

            }
        }

        return $result;
    }

    public function getUsersPermissions(array $userIds): array
    {
        return DB::table('model_has_permissions')
            ->whereIn('model_id', $userIds)
            ->where('model_type', EloquentUser::class)
            ->select('model_id', DB::raw('GROUP_CONCAT(permission_id) as permissions'))
            ->groupBy('model_id')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->model_id => $item->permissions];
            })
            ->toArray();
    }

    public function getUsersRoles(array $userIds): array
    {
        return DB::table('model_has_roles')
            ->whereIn('model_id', $userIds)
            ->where('model_type', EloquentUser::class)
            ->select('model_id', DB::raw('GROUP_CONCAT(role_id) as roles'))
            ->groupBy('model_id')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->model_id => $item->roles];
            })
            ->toArray();
    }

    public function updateUserRoles(int $userId, array $rolesToAdd, array $rolesToRemove): ?RolesUpdatedToUserResponse
    {
        return DB::transaction(function () use ($userId, $rolesToAdd, $rolesToRemove) {

            $user = User::findOrFail($userId);
            $actuallyAdded = [];
            $actuallyRemoved = [];
            if (!empty($rolesToAdd)) {
                foreach ($rolesToAdd as $role) {
                    if (!$user->hasRole($role)) {
                        $user->assignRole($role);
                        $actuallyAdded[] = $role;
                    }
                }
            }
            if (!empty($rolesToRemove)) {
                foreach ($rolesToRemove as $role) {
                    if ($user->hasRole($role)) {
                        $user->removeRole($role);
                        $actuallyRemoved[] = $role;
                    }
                }
            }
            if($user->hasRole(UserRoles::UNVERIFIED->value)) {
                $user->removeRole(UserRoles::UNVERIFIED);
                $actuallyRemoved[] = UserRoles::UNVERIFIED->value;
            }
            $user->load('roles');

            $rolesUpdated = [
                'rolesAdded' => $actuallyAdded,
                'rolesRemoved' => $actuallyRemoved,
                'currentRoles' => $user->roles->pluck('name')->toArray(),
            ];
            return GeneralMapper::toRolesUpdatedToUserResponse($user, $rolesUpdated);
        });
    }

    public function updateUserPermissions(int $userId, array $permissionsToAdd, array $permissionsToRemove): ?PermissionsUpdatedToUserResponse
    {

        return DB::transaction(function () use ($userId, $permissionsToAdd, $permissionsToRemove) {

            $user = User::findOrFail($userId);
            $actuallyAdded = [];
            $actuallyRemoved = [];
            if (!empty($permissionsToAdd)) {
                foreach ($permissionsToAdd as $permission) {
                    if (!$user->hasPermissionTo($permission)) {
                        $user->givePermissionTo($permission);
                        $actuallyAdded[] = $permission;
                    }
                }
            }

            if (!empty($permissionsToRemove)) {
                foreach ($permissionsToRemove as $permission) {
                    if ($user->hasPermissionTo($permission)) {
                        $user->revokePermissionTo($permission);
                        $actuallyRemoved[] = $permission;
                    }
                }

            }

            $user->load('permissions');

            $permissionsUpdated = [
                'permissionsAdded' => $actuallyAdded,
                'permissionsRemoved' => $actuallyRemoved,
                'currentPermissions' => $user->permissions->pluck('name')->toArray(),
            ];

            return GeneralMapper::toPermissionsUpdatedToUserResponse($user, $permissionsUpdated);
        });
    }
}
