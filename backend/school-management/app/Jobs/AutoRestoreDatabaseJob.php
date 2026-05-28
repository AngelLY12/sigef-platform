<?php

namespace App\Jobs;

use App\Core\Application\UseCases\Jobs\RestoreDatabaseUseCase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutoRestoreDatabaseJob implements ShouldQueue
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
    public function handle(): void
    {
        $restore = app(RestoreDatabaseUseCase::class);
        $success = $restore->execute();
        if ($success) {
            Log::info("Se restauro la base de datos o no hay nada que restaurar");
        }else{
            Log::info("No se pudo restaurar la base de datos.");

        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("Job falló restaurando base de datos", [
            'error' => $exception->getMessage()
        ]);
    }
}
