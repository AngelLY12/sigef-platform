<?php

namespace App\Core\Application\UseCases\Admin\RolePermissionManagement;

use App\Core\Domain\Entities\Permission;
use App\Core\Domain\Repositories\Query\Auth\RolesAndPermissosQueryRepInterface;
use App\Exceptions\NotFound\PermissionNotFoundException;

class FindPermissionByIdUseCase
{
    public function __construct(
        private RolesAndPermissosQueryRepInterface $rpqRepo
    )
    {
    }

    public function execute(int $id): Permission
    {
        $permission = $this->rpqRepo->findPermissionById($id);
        if(!$permission)
        {
            throw new PermissionNotFoundException();
        }
        return $permission;
    }
}
