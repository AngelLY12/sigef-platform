<?php

namespace App\Core\Application\Services\Admin;

use App\Core\Application\DTO\Request\User\UpdateUserPermissionsDTO;
use App\Core\Application\DTO\Request\User\UpdateUserRoleDTO;
use App\Core\Application\DTO\Response\General\PermissionsByRole;
use App\Core\Application\DTO\Response\General\PermissionsByUsers;
use App\Core\Application\DTO\Response\General\PermissionsUpdatedToUserResponse;
use App\Core\Application\DTO\Response\General\RolesUpdatedToUserResponse;
use App\Core\Application\DTO\Response\User\UserWithUpdatedPermissionsResponse;
use App\Core\Application\DTO\Response\User\UserWithUpdatedRoleResponse;
use App\Core\Application\Traits\HasCache;
use App\Core\Application\UseCases\Admin\RolePermissionManagement\FindAllPermissionsByCurpsUseCase;
use App\Core\Application\UseCases\Admin\RolePermissionManagement\FindAllPermissionsByRoleUseCase;
use App\Core\Application\UseCases\Admin\RolePermissionManagement\FindAllRolesUseCase;
use App\Core\Application\UseCases\Admin\RolePermissionManagement\FindPermissionByIdUseCase;
use App\Core\Application\UseCases\Admin\RolePermissionManagement\FindPermissionsToSingleUserUseCase;
use App\Core\Application\UseCases\Admin\RolePermissionManagement\FindRoleByIdUseCase;
use App\Core\Application\UseCases\Admin\RolePermissionManagement\SyncPermissionsUseCase;
use App\Core\Application\UseCases\Admin\RolePermissionManagement\SyncRoleUseCase;
use App\Core\Application\UseCases\Admin\RolePermissionManagement\UpdatePermissionsToSingleUserUseCase;
use App\Core\Application\UseCases\Admin\RolePermissionManagement\UpdateRolesToSingleUserUseCase;
use App\Core\Domain\Entities\Permission;
use App\Core\Domain\Entities\Role;
use App\Core\Domain\Enum\Cache\AdminCacheSufix;
use App\Core\Domain\Enum\Cache\CachePrefix;
use App\Core\Infraestructure\Cache\CacheService;

class AdminRolePermissionsServiceFacades
{
    use HasCache;
    private const TAG_USERS_ALL = [CachePrefix::ADMIN->value, AdminCacheSufix::USERS->value, "all"];
    private const TAG_USERS_ID = [CachePrefix::ADMIN->value, AdminCacheSufix::USERS->value, "all:id"];
    private const TAG_ROLES = [CachePrefix::ADMIN->value, AdminCacheSufix::ROLES->value];
    private const TAG_PERMISSIONS_BY_ROLE = [CachePrefix::ADMIN->value, AdminCacheSufix::PERMISSIONS_BY_ROLE->value];
    private array $requestCache = [];

    public function __construct(
        private SyncPermissionsUseCase           $sync,
        private FindAllRolesUseCase              $roles,
        private FindAllPermissionsByCurpsUseCase $permissions,
        private FindAllPermissionsByRoleUseCase      $permissionsByRole,
        private FindRoleByIdUseCase              $role,
        private FindPermissionByIdUseCase        $permission,
        private SyncRoleUseCase                  $syncRoles,
        private FindPermissionsToSingleUserUseCase $permissionsBySingleUser,
        private UpdatePermissionsToSingleUserUseCase $updatePermissionsToSingleUserUseCase,
        private UpdateRolesToSingleUserUseCase $updateRolesToSingleUserUseCase,
        private CacheService                     $service


    ){
        $this->setCacheService($service);
    }

    public function updateRolesToUser(int $userId, array $rolesToAdd, array $rolesToRemove): RolesUpdatedToUserResponse
    {
        return $this->idempotent(
            'update_roles_to_user',
            [
                'user_id' => $userId,
                'add' => $rolesToAdd,
                'remove' => $rolesToRemove,
            ],
            function () use ($userId, $rolesToAdd, $rolesToRemove) {

                $roles = $this->updateRolesToSingleUserUseCase
                    ->execute($userId, $rolesToAdd, $rolesToRemove);

                $this->service->flushTags(array_merge(self::TAG_USERS_ID,["userId:{$userId}"]));
                $this->service->flushTags(self::TAG_USERS_ALL);

                return $roles;
            }
        );
    }

    public function updatePermissionsToUser(int $userId, array $permissionsToAdd, array $permissionsToRemove): PermissionsUpdatedToUserResponse
    {
        return $this->idempotent(
            'update_permissions_to_user',
            [
                'user_id' => $userId,
                'add' => $permissionsToAdd,
                'remove' => $permissionsToRemove,
            ],
            function () use ($userId, $permissionsToAdd, $permissionsToRemove) {

                $permissions = $this->updatePermissionsToSingleUserUseCase
                    ->execute($userId, $permissionsToAdd, $permissionsToRemove);

                $this->service->flushTags(array_merge(self::TAG_USERS_ID,["userId:{$userId}"]));
                $this->service->flushTags(array_merge(self::TAG_PERMISSIONS_BY_ROLE,["userId:{$userId}"]));

                return $permissions;
            }
        );

    }
    public function syncPermissions(UpdateUserPermissionsDTO $dto): UserWithUpdatedPermissionsResponse
    {
        return $this->idempotent(
            'sync_permissions',
            $dto->toArray(),
            function () use ($dto) {
                $permissions=$this->sync->execute($dto);
                $this->service->flushTags(self::TAG_USERS_ID);
                return $permissions;
            }
        );
    }

    public function syncRoles(UpdateUserRoleDTO $dto):UserWithUpdatedRoleResponse
    {
        return $this->idempotent(
            'sync_roles',
            $dto->toArray(),
            function () use ($dto) {
                $roles = $this->syncRoles->execute($dto);
                $this->service->flushTags(self::TAG_USERS_ID);
                $this->service->flushTags(self::TAG_USERS_ALL);
                return $roles;
            }
        );

    }

    public function findPermissionsToSingleUser(int $userId, array $roles, bool $forceRefresh): array
    {
        $key=$this->generateCacheKey(CachePrefix::ADMIN->value,
            AdminCacheSufix::PERMISSIONS_BY_ROLE->value,
            [
                "userId:$userId",
            ]
        );
        $tags = array_merge(self::TAG_PERMISSIONS_BY_ROLE, ["userId:$userId"]);
        return $this->shortCache($key, fn()=> $this->permissionsBySingleUser->execute($userId, $roles), $tags, $forceRefresh);
    }
    public function findAllPermissionsByCurps(array $curps): PermissionsByUsers
    {
        $key = implode(',', $curps);
        if (isset($this->requestCache[$key])) {
            return $this->requestCache[$key];
        }
        $permissions = $this->permissions->execute($curps);
        $this->requestCache[$key] = $permissions;

        return $permissions;
    }

    public function findAllPermissionsByRole(string $role, bool $forceRefresh): PermissionsByRole
    {
        $key=$this->generateCacheKey(CachePrefix::ADMIN->value, AdminCacheSufix::PERMISSIONS_BY_ROLE->value);
        return $this->weeklyCache($key, fn()=>$this->permissionsByRole->execute($role), self::TAG_PERMISSIONS_BY_ROLE, $forceRefresh);
    }

    public function findAllRoles(bool $forceRefresh): array
    {
        $key=$this->generateCacheKey(CachePrefix::ADMIN->value, AdminCacheSufix::ROLES->value);
        return $this->weeklyCache($key, fn() => $this->roles->execute(), self::TAG_ROLES ,$forceRefresh);
    }

    public function findPermissionById(int $id): Permission
    {
        return  $this->permission->execute($id);
    }
    public function findRolById(int $id): Role
    {
        return $this->role->execute($id);
    }

}
