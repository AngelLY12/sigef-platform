<?php

namespace App\Core\Application\UseCases\Admin\RolePermissionManagement;

use App\Core\Application\DTO\Response\General\PermissionsByUsers;
use App\Core\Domain\Repositories\Query\Auth\RolesAndPermissosQueryRepInterface;
use App\Exceptions\NotFound\PermissionsByUserNotFoundException;

class FindAllPermissionsByCurpsUseCase
{
    public function __construct(
        private RolesAndPermissosQueryRepInterface $rpqRepo
    )
    {
    }

    public function execute(array $curps): PermissionsByUsers
    {
        $permissionsByUsers=$this->rpqRepo->findPermissionsApplicableByCurps($curps);
        if(empty($permissionsByUsers))
        {
            throw new PermissionsByUserNotFoundException();
        }
        return $permissionsByUsers;
    }
}
