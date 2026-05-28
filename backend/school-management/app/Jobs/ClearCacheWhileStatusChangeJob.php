<?php

namespace App\Jobs;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Infraestructure\Cache\CacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
class ClearCacheWhileStatusChangeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    private int $userId;
    private PaymentConceptStatus $status;
    public function __construct(
        int $userId,
        PaymentConceptStatus $status
    )
    {
        $this->userId=$userId;
        $this->status=$status;
    }

    /**
     * Execute the job.
     */
     public function handle(CacheService $cacheService): void
    {
        $cacheService->clearCacheWhileConceptChangeStatus($this->userId, $this->status);
        Log::info("Cache de staff limpiado correctamente");
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("Job falló limpiando cache de usuario al actualizar concepto: {$this->userId}", [
            'error' => $exception->getMessage()
        ]);
    }

}
