<?php

namespace App\Core\Domain\Enum\Events\Sources;
/**
 * @OA\Schema(
 *     schema="EmailEventSourceType",
 *     type="string",
 *     description="Origen del evento de correo.",
 *     enum={
 *         "stripe",
 *         "user",
 *         "concept",
 *         "system"
 *     },
 *     example="user"
 * )
 *
 */
enum EmailEventSourceType: string
{
    case STRIPE = 'stripe';
    case USER = 'user';
    case CONCEPT = 'concept';
    case SYSTEM = 'system';

    public function label(): string
    {
        return match ($this) {
            self::STRIPE => 'Pasarela de pago',
            self::USER => 'Usuario',
            self::CONCEPT => 'Concepto',
            self::SYSTEM => 'Sistema',
        };
    }

}
