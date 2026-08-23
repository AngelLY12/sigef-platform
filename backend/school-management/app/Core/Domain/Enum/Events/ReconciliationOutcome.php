<?php

namespace App\Core\Domain\Enum\Events;

/**
 * @OA\Schema(
 *     schema="ReconciliationOutcome",
 *     type="string",
 *     description="Resultado obtenido durante la reconciliación de un pago.",
 *     enum={
 *         "matched",
 *         "corrected",
 *         "failed",
 *         "mismatch"
 *     },
 *     example="matched"
 * )
 */
enum ReconciliationOutcome: string
{
    case MATCHED = 'matched';
    case CORRECTED = 'corrected';
    case FAILED = 'failed';
    case MISMATCH = 'mismatch';

    public function label(): string
    {
        return match ($this) {
            self::MATCHED => 'Coincidente',
            self::CORRECTED => 'Corregido',
            self::FAILED => 'Fallido',
            self::MISMATCH => 'No coincidente',
        };
    }
}
