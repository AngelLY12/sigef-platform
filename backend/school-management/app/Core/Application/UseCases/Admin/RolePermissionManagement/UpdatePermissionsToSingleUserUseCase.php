<?php

namespace App\Core\Application\UseCases\Admin\RolePermissionManagement;

use App\Core\Application\DTO\Response\General\PermissionsUpdatedToUserResponse;
use App\Core\Domain\Repositories\Command\Auth\RolesAndPermissionsRepInterface;
use App\Exceptions\Validation\ValidationException;

class UpdatePermissionsToSingleUserUseCase
{
    public function __construct(
        private RolesAndPermissionsRepInterface $rpRepo,
    ){}

    public function execute(int $userId, array $permissionsToAdd, array $permissionsToRemove): PermissionsUpdatedToUserResponse
    {
        if(empty($permissionsToAdd) && empty($permissionsToRemove)) {
            throw new ValidationException("Debe haber por lo menos un permiso para agregar o remover");
        }
        $permissionsIntersect = array_intersect($permissionsToAdd, $permissionsToRemove);
        if(!empty($permissionsIntersect)) {
            throw new ValidationException(
                "Los siguientes permisos no pueden estar simultáneamente en agregar y remover: "
                . implode(', ', $permissionsIntersect)
            );
        }

        $response= $this->rpRepo->updateUserPermissions($userId, $permissionsToAdd, $permissionsToRemove);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        return $response;
    }

}
