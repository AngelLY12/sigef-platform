<?php

namespace App\Core\Domain\Enum\User;

/**
 * @OA\Schema(
 *     schema="UserStatus",
 *     type="string",
 *     description="Estatus valido de un usuario",
 *     enum={"activo", "baja", "eliminado", "baja-temporal"},
 *     example="activo"
 * )
 */
enum UserStatus: string
{
    case ACTIVO = 'activo';
    case BAJA_TEMPORAL = 'baja-temporal';
    case BAJA = 'baja';
    case ELIMINADO = 'eliminado';

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::ACTIVO => [self::BAJA, self::BAJA_TEMPORAL,self::ELIMINADO],
            self::BAJA => [self::ACTIVO, self::ELIMINADO],
            self::BAJA_TEMPORAL => [self::ACTIVO,self::BAJA, self::ELIMINADO],
            self::ELIMINADO => [self::ACTIVO],
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus, $this->allowedTransitions(), true);
    }
     public function isUpdatable(): bool
    {
        return in_array($this, [self::ACTIVO], true);
    }
}

