<?php

namespace App\Jobs;

use App\Core\Infraestructure\Cache\CacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClearStudentCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private int $userId;
    /**
     * Create a new job instance.
     */
    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(CacheService $cacheService): void
    {
        $cacheService->clearStudentCache($this->userId);
        Log::info("Cache de estudiante limpiado correctamente");
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("Job falló limpiando cache de estudiante: {$this->userId}", [
            'error' => $exception->getMessage()
        ]);
    }
}
