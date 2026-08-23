<?php

namespace App\Core\Domain\Enum\Events\Status;

/**
 * @OA\Schema(
 *     schema="ReconciliationEventStatus",
 *     type="string",
 *     description="Estado de un evento de reconciliación.",
 *     enum={
 *         "pending",
 *         "completed",
 *         "failed"
 *     },
 *     example="completed"
 * )
 */
enum ReconciliationEventStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pendiente',
            self::COMPLETED => 'Completada',
            self::FAILED => 'Fallida',
        };
    }
}
