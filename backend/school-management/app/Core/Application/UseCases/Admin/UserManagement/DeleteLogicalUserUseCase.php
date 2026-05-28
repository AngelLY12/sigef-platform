<?php

namespace App\Core\Application\UseCases\Admin\UserManagement;

use App\Core\Application\UseCases\Admin\Shared\BaseChangeUserStatusUseCase;
use App\Core\Domain\Enum\User\UserStatus;
use App\Core\Domain\Utils\Validators\UserValidator;

class DeleteLogicalUserUseCase extends BaseChangeUserStatusUseCase
{
    protected function getTargetStatus(): UserStatus
    {
        return UserStatus::ELIMINADO;
    }

    protected function validateUsers(iterable $users): void
    {
        foreach ($users as $user) {
            UserValidator::ensureValidStatusTransition(
                $user,
                UserStatus::ELIMINADO
            );
        }
    }

}
