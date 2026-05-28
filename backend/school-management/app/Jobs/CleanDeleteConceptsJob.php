<?php

namespace App\Jobs;

use App\Core\Application\UseCases\Jobs\CleanDeletedConceptsUseCase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanDeleteConceptsJob implements ShouldQueue
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
    public function handle(CleanDeletedConceptsUseCase $clean): void
    {
         $deleted = $clean->execute();
        if ($deleted > 0) {
            Log::info("Se eliminaron {$deleted} conceptos con estatus eliminado.");
        }else{
            Log::info("No se encontraron conceptos para eliminar.");

        }
    }
    public function failed(\Throwable $exception): void
    {
        Log::critical("Job falló eliminando conceptos", [
            'error' => $exception->getMessage()
        ]);
    }
}
