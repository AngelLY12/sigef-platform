<?php

namespace App\Core\Application\UseCases\Admin\RolePermissionManagement;

use App\Core\Application\DTO\Response\General\PermissionsByRole;
use App\Core\Domain\Repositories\Query\Auth\RolesAndPermissosQueryRepInterface;
use App\Exceptions\NotFound\PermissionsByUserNotFoundException;

class FindAllPermissionsByRoleUseCase
{
    public function __construct(
        private RolesAndPermissosQueryRepInterface $rpqRepo
    ){}

    public function execute(string $role): PermissionsByRole
    {
        $permissions = $this->rpqRepo->findPermissionsApplicableByRole($role);
        if(!$permissions)
        {
            throw new PermissionsByUserNotFoundException();
        }
        return $permissions;
    }

}
