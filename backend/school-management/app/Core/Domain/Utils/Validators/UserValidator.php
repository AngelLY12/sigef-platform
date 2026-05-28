<?php

namespace App\Core\Domain\Utils\Validators;

use App\Core\Domain\Entities\User;
use App\Core\Domain\Enum\User\UserRoles;
use App\Core\Domain\Enum\User\UserStatus;
use App\Exceptions\Conflict\UserAlreadyActiveException;
use App\Exceptions\Conflict\UserAlreadyDeletedException;
use App\Exceptions\Conflict\UserAlreadyDisabledException;
use App\Exceptions\Conflict\UserCannotBeDisabledException;
use App\Exceptions\Conflict\UserCannotBeUpdatedException;
use App\Exceptions\Conflict\UserConflictStatusException;
use App\Exceptions\Unauthorized\UserInactiveException;

class UserValidator
{
    public static function ensureValidStatusTransition(User $user, UserStatus $newStatus)
    {
        $current = $user->status;

        if ($current === $newStatus) {

            throw match ($newStatus) {
                UserStatus::ACTIVO     => new UserAlreadyActiveException(),
                UserStatus::BAJA, UserStatus::BAJA_TEMPORAL => new UserAlreadyDisabledException(),
                UserStatus::ELIMINADO  => new UserAlreadyDeletedException()
            };
        }

        if (!$current->canTransitionTo($newStatus)) {
            throw match ($newStatus) {
                UserStatus::BAJA      => new UserCannotBeDisabledException(),
                default               => new UserConflictStatusException("Transición inválida, no se puede pasar de {$current->value} a {$newStatus->value}"),
            };
        }
    }
    public static function ensureUserIsValidToUpdate(User $user){
        if (!$user->status->isUpdatable()) {
            throw new UserCannotBeUpdatedException();
        }
    }

    public static function ensureUserIsActive(User $user)
    {
        if(!$user->isActive())
        {
            throw new UserInactiveException();
        }
    }

}
