<?php

namespace App\Core\Application\UseCases\Admin\RolePermissionManagement;

use App\Core\Domain\Repositories\Query\Auth\RolesAndPermissosQueryRepInterface;
use App\Exceptions\NotFound\PermissionsByUserNotFoundException;

class FindPermissionsToSingleUserUseCase
{
    public function __construct(
        private RolesAndPermissosQueryRepInterface $rpqRepo,
    ){}

    public function execute(int $userId, array $roles): array
    {
        $permissions = $this->rpqRepo->findPermissionsApplicablesToUser($userId, $roles);
        if (empty($permissions)) {
            throw new PermissionsByUserNotFoundException();
        }
        return $permissions;
    }

}
