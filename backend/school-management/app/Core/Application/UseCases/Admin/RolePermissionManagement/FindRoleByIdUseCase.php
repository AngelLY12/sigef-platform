<?php

namespace App\Core\Application\UseCases\Admin\RolePermissionManagement;

use App\Core\Domain\Entities\Role;
use App\Core\Domain\Repositories\Query\Auth\RolesAndPermissosQueryRepInterface;
use App\Exceptions\NotFound\RoleNotFoundException;

class FindRoleByIdUseCase
{
    public function __construct(private RolesAndPermissosQueryRepInterface $rpqRepo)
    {

    }
    public function execute(int $id): Role
    {
        $role=$this->rpqRepo->findRoleById($id);
        if(!$role)
        {
            throw new RoleNotFoundException();
        }
        return $role;
    }
}
