<?php

namespace App\Core\Application\UseCases\Admin\RolePermissionManagement;

use App\Core\Application\DTO\Response\General\RolesUpdatedToUserResponse;
use App\Core\Domain\Repositories\Command\Auth\RolesAndPermissionsRepInterface;
use App\Exceptions\Validation\ValidationException;

class UpdateRolesToSingleUserUseCase
{
    public function __construct(
        private RolesAndPermissionsRepInterface $rpRepo
    ){}

    public function execute(int $userId, array $rolesToAdd, array $rolesToRemove): RolesUpdatedToUserResponse
    {
        if(empty($rolesToAdd) && empty($rolesToRemove)) {
            throw new ValidationException("Debe haber por lo menos un rol para agregar o remover");
        }
        $rolesIntersect = array_intersect($rolesToAdd, $rolesToRemove);
        if(!empty($rolesIntersect)) {
            throw new ValidationException(
                "Los siguientes roles no pueden estar simultáneamente en add y remove: "
                . implode(', ', $rolesIntersect)
            );
        }
        $result = $this->rpRepo->updateUserRoles($userId, $rolesToAdd, $rolesToRemove);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        return $result;
    }

}
