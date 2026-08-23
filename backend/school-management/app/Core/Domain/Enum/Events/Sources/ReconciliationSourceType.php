<?php

namespace App\Core\Domain\Enum\Events\Sources;

/**
 * @OA\Schema(
 *     schema="ReconciliationSourceType",
 *     type="string",
 *     description="Origen del evento de reconciliación.",
 *     enum={
 *         "manual",
 *         "system"
 *     },
 *     example="system"
 * )
 */
enum ReconciliationSourceType: string
{
    case MANUAL = 'manual';
    case SYSTEM = 'system';

    public function label(): string
    {
        return match ($this) {
            self::MANUAL => 'Manual',
            self::SYSTEM => 'Sistema',
        };
    }
}
