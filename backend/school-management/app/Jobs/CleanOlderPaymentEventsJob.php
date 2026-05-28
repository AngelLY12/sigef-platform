<?php

namespace App\Jobs;

use App\Core\Application\UseCases\Jobs\CleanOlderPaymentEventsUseCase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanOlderPaymentEventsJob implements ShouldQueue
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
    public function handle(CleanOlderPaymentEventsUseCase $case): void
    {
        $deleted = $case->execute();

        if ($deleted > 0) {
            Log::info("Se eliminaron {$deleted} PaymentEvents.");
        }
        else{
            Log::info("No se encontraron PaymentEvents para eliminar.");
        }
    }
    public function failed(\Throwable $exception): void
    {
        Log::critical("Job falló eliminando logs viejos", [
            'error' => $exception->getMessage()
        ]);
    }
}
