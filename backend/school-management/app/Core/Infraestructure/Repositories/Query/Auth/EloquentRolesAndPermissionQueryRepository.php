<?php

namespace App\Core\Infraestructure\Repositories\Query\Auth;

use App\Core\Application\DTO\Response\General\PermissionsByRole;
use App\Core\Application\DTO\Response\General\PermissionsByUsers;
use App\Core\Application\Mappers\GeneralMapper;
use App\Core\Domain\Entities\Permission as EntitiesPermission;
use App\Core\Domain\Entities\Role as EntitiesRole;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Repositories\Query\Auth\RolesAndPermissosQueryRepInterface;
use App\Core\Infraestructure\Mappers\RolesAndPermissionMapper;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class EloquentRolesAndPermissionQueryRepository implements RolesAndPermissosQueryRepInterface
{
    public function findRoleById(int $id): ?EntitiesRole
    {
       return optional(Role::find($id),fn($role)=>RolesAndPermissionMapper::toRoleDomain($role));
    }
    public function findRoleByName(string $name): ?EntitiesRole
    {
        return optional(Role::where('name',$name)->first(),fn($role)=>RolesAndPermissionMapper::toRoleDomain($role));
    }
    public function findAllRoles(): array
    {
        return Role::select('id','name')
        ->where('hidden', false)
        ->get()
        ->map(fn($role)=>RolesAndPermissionMapper::toRoleDomain($role))
        ->toArray();
    }
    public function findPermissionById(int $id): ?EntitiesPermission
    {
        return optional(Permission::find($id),fn($permission)=>RolesAndPermissionMapper::toPermissionDomain($permission));
    }

    public function findPermissionsApplicableByRole(string $role): ?PermissionsByRole
    {
        $totalUsers = User::role($role)->count();
        if ($totalUsers === 0) {
            return null;
        }
        $permissions = $this->getPermissionsForRole($role);

        return GeneralMapper::toPermissionsByRole(
            role: $role,
            usersCount: $totalUsers,
            permissions: $permissions
        );
    }

    public function findPermissionsApplicableByCurps(array $curps): ?PermissionsByUsers
    {
        if (empty($curps)) {
            return null;
        }
        $validCurps = array_slice(array_unique(array_map('trim', $curps)),0,100);

        if (empty($validCurps)) {
            return null;
        }
        $users = User::with('roles:name')
            ->whereIn('curp', $validCurps)
            ->whereHas('roles')
            ->get(['id', 'curp']);

        if ($users->isEmpty()) {
            return null;
        }

        $userItems = $users->map(function($user) {
            return [
                'id' => $user->id,
                'curp' => $user->curp,
                'roles' => $user->roles->pluck('name')->toArray()
            ];
        })->toArray();

        $allRoleNames = collect($userItems)
            ->flatMap(fn($user) => $user['roles'])
            ->unique()
            ->values()
            ->toArray();

        if (empty($allRoleNames)) {
            $allRoleNames = Role::whereHas('users', function($q) use ($users) {
                $q->whereIn('users.id', $users->pluck('id'));
            })
                ->pluck('name')
                ->toArray();
        }

        $permissionsByRole = [];
        foreach ($allRoleNames as $roleName) {
            $permissionsByRole[] = [
                'role' => $roleName,
                'permissions' => $this->getPermissionsForRole($roleName)
            ];
        }

        return GeneralMapper::toPermissionsByUsers(
            roles: $allRoleNames,
            users: $userItems,
            permissions: $permissionsByRole
        );
    }

    public function findPermissionsApplicablesToUser(int $userId, array $roles): ?array
    {
        $userRoles = Role::whereHas('users', function($query) use ($userId) {
            $query->where('users.id', $userId);
        })
            ->pluck('name')
            ->toArray();

        if (empty($userRoles)) {
            return null;
        }

        $rolesToCheck = empty($roles)
            ? $userRoles
            : array_intersect($roles, $userRoles);

        if (empty($rolesToCheck)) {
            return null;
        }

        return collect($rolesToCheck)
            ->flatMap(fn($role) => $this->getPermissionsForRole($role))
            ->unique(fn($permission) => $permission->id)
            ->values()
            ->toArray();
    }

    public function findPermissionIds(array $names, string $role): array
    {
        return \App\Models\Permission::whereIn('name', $names)
            ->whereHas('contexts', fn($q) => $q->where('target_role', $role))
            ->pluck('id')
            ->toArray();
    }

    public function getRoleIdsByNames(array $names): array
    {
        return Role::whereIn('name', $names)->pluck('id')->toArray();
    }

    public function hasAdminAssignError(int $adminRoleId, array $rolesToAddIds, Collection $users): bool
    {
        if (!in_array($adminRoleId, $rolesToAddIds)) return false;

        $existingAdmin = DB::table('model_has_roles')
            ->where('role_id', $adminRoleId)
            ->where('model_type', User::class)
            ->first();

        if (!$existingAdmin) return false;

        $targetIds = $users->pluck('id')->toArray();
        return !in_array($existingAdmin->model_id, $targetIds);
    }

    public function hasAdminRemoveError(int $adminRoleId, array $rolesToRemoveIds, Collection $users): bool
    {
        if (!in_array($adminRoleId, $rolesToRemoveIds)) return false;

        $currentAdmins = DB::table('model_has_roles')
            ->where('role_id', $adminRoleId)
            ->where('model_type', User::class)
            ->pluck('model_id')
            ->toArray();

        $targetIds = $users->pluck('id')->toArray();

        return !empty(array_diff($currentAdmins, $targetIds));
    }

    public function hasAdminMissingError(int $adminRoleId, array $rolesToRemoveIds, array $rolesToAddIds): bool
    {
        if (!in_array($adminRoleId, $rolesToRemoveIds)) {
            return false;
        }
        $hasReplacement = in_array($adminRoleId, $rolesToAddIds);
        if ($hasReplacement) {
            return false;
        }

        $adminCount = DB::table('model_has_roles')
            ->where('role_id', $adminRoleId)
            ->where('model_type', User::class)
            ->count();

        return $adminCount <= 1;
    }

    private function getPermissionsForRole(string $roleName): array
    {

        return \App\Models\Permission::where('type', 'model')
            ->whereHas('contexts', fn($q) => $q->where('target_role', $roleName))
            ->select('id', 'name', 'type')
            ->get()
            ->map(fn($permission) => GeneralMapper::toPermissionToDisplay($permission))
            ->toArray();
    }

}
