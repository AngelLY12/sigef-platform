<?php

namespace App\Core\Domain\Utils\Validators;

use App\Core\Domain\Entities\User;
use App\Exceptions\Validation\ValidationException;

class StripeValidator
{
    public static function validateUserForStripe(User $user): void
    {
        if (empty($user->email)) {
            throw new ValidationException("El usuario no tiene correo electrónico");
        }

        if (empty($user->name)) {
            throw new ValidationException("El usuario no tiene nombre definido");
        }
    }

    public static function validateStripeId(?string $id, string $prefix, string $fieldName): void
    {
        if (empty($id)) {
            throw new ValidationException("El ID de {$fieldName} no puede ser vacío");
        }

        if (!preg_match("/^{$prefix}_[a-zA-Z0-9_]+$/", $id)) {
            throw new ValidationException("El ID de {$fieldName} tiene un formato inválido");
        }
    }


}
