<?php

namespace App\Core\Domain\Enum\Events\Status;

/**
 * @OA\Schema(
 *     schema="EmailEventStatus",
 *     type="string",
 *     description="Estado de un evento de correo.",
 *     enum={
 *         "pending",
 *         "sent",
 *         "delivered",
 *         "failed"
 *     },
 *     example="delivered"
 * )
 */
enum EmailEventStatus: string
{
    case PENDING = 'pending';
    case SENT = 'sent';
    case DELIVERED = 'delivered';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::SENT => 'Enviado',
            self::DELIVERED => 'Entregado',
            self::FAILED => 'Fallido',
        };
    }
}
