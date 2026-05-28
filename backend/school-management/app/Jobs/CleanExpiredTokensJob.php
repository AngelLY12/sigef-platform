<?php

namespace App\Jobs;

use App\Core\Application\UseCases\Jobs\CleanExpiredAccessTokensUseCase;
use App\Core\Application\UseCases\Jobs\CleanExpiredTokensUseCase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanExpiredTokensJob implements ShouldQueue
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
    public function handle(CleanExpiredAccessTokensUseCase $clean): void
    {
        $deleted = $clean->execute();

        if ($deleted > 0) {
            Log::info("Se eliminaron {$deleted} tokens de Sanctum expirados o revocados.");
        }
        if ($deleted=== 0) {
            Log::info("No se encontraron tokens expirados o revocados para eliminar.");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("Job falló limpiando tokens de acceso expirados", [
            'error' => $exception->getMessage()
        ]);
    }
}
