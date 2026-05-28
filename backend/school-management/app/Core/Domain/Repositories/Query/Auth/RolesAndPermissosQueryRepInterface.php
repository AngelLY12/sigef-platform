<?php

namespace App\Core\Domain\Repositories\Query\Auth;

use App\Core\Application\DTO\Response\General\PermissionsByRole;
use App\Core\Application\DTO\Response\General\PermissionsByUsers;
use App\Core\Domain\Entities\Permission;
use App\Core\Domain\Entities\Role;
use Illuminate\Support\Collection;

interface RolesAndPermissosQueryRepInterface
{
    public function findRoleById(int $id):?Role;
    public function findRoleByName(string $name): ?Role;
    public function findAllRoles(): array;
    public function findPermissionById(int $id):?Permission;
    public function findPermissionsApplicableByRole(string $role): ?PermissionsByRole;
    public function findPermissionsApplicableByCurps(array $curps): ?PermissionsByUsers;
    public function findPermissionsApplicablesToUser(int $userId, array $roles): ?array;
    public function findPermissionIds(array $names, string $role): array;
    public function getRoleIdsByNames(array $names): array;
    public function hasAdminAssignError(int $adminRoleId, array $rolesToAddIds, Collection $users): bool;
    public function hasAdminRemoveError(int $adminRoleId, array $rolesToRemoveIds, Collection $users): bool;
    public function hasAdminMissingError(int $adminRoleId, array $rolesToRemoveIds, array $rolesToAddIds): bool;


}
