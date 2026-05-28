<?php

namespace App\Core\Application\UseCases\Admin\RolePermissionManagement;

use App\Core\Domain\Repositories\Query\Auth\RolesAndPermissosQueryRepInterface;

class FindAllRolesUseCase
{
    public function __construct(
        private RolesAndPermissosQueryRepInterface $rpqRepo
    )
    {
    }
    public function execute(): array
    {
        return $this->rpqRepo->findAllRoles();
    }
}
