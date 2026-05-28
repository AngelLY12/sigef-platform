<?php

namespace App\Jobs;

use App\Core\Infraestructure\Cache\CacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClearStaffCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(CacheService $cacheService): void
    {
        $cacheService->clearStaffCache();
        Log::info("Cache de staff limpiado correctamente");
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("Job falló limpiando cache de staff", [
            'error' => $exception->getMessage()
        ]);
    }
}
