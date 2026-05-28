<?php

namespace App\Core\Application\DTO\Response\General;

/**
 * @OA\Schema(
 *     schema="ReconciliationResult",
 *     type="object",
 *     @OA\Property(property="processed", type="integer", example=0),
 *     @OA\Property(property="updated", type="integer", example=0),
 *     @OA\Property(property="notified", type="integer", example=0),
 *     @OA\Property(property="failed", type="integer", example=0),
 *
 * )
 */
class ReconciliationResult
{
    public function __construct(
        public int $processed = 0,
        public int $updated = 0,
        public int $notified = 0,
        public int $failed = 0,
    )
    {}

    public function toArray(): array
    {
        return [
            'processed' => $this->processed,
            'updated' => $this->updated,
            'notified' => $this->notified,
            'failed' => $this->failed,
        ];
    }

}
