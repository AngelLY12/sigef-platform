<?php

namespace App\Core\Domain\Repositories\Command\Auth;

use App\Core\Application\DTO\Response\General\PermissionsUpdatedToUserResponse;
use App\Core\Application\DTO\Response\General\RolesUpdatedToUserResponse;
use App\Models\User;
use Illuminate\Support\Collection;

interface RolesAndPermissionsRepInterface
{
    public function assignRoles(array $roleRows): void;
    public function givePermissionsByType(User $user, string $targetRole, string $type = 'model'): void;
    public function removePermissions(array $userIds, array $permissionIds): int;
    public function addPermissions(array $userIds, array $permissionIds): int;
    public function getUsersWithPermissionChanges(
        array $userIds,
        array $permissionsToAddIds,
        array $permissionsToRemoveIds
    ): array;
    public function syncRoles(Collection $users, array $rolesToAddIds, array $rolesToRemoveIds): array;
    public function getUsersPermissions(array $userIds): array;
    public function getUsersRoles(array $userIds): array;
    public function updateUserRoles(int $userId, array $rolesToAdd, array $rolesToRemove): ?RolesUpdatedToUserResponse;
    public function updateUserPermissions(int $userId, array $permissionsToAdd, array $permissionsToRemove): ?PermissionsUpdatedToUserResponse;

}
