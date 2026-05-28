<?php

namespace App\Jobs;

use App\Core\Application\UseCases\Jobs\CleanExpiredInvitesUseCase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
class CleanExpiredInvitesJob implements ShouldQueue
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
    public function handle(CleanExpiredInvitesUseCase $clean): void
    {
        $deleted = $clean->execute();

        if ($deleted > 0) {
            Log::info("Se eliminaron {$deleted} invitaciones expiradas.");
        }
        if ($deleted=== 0) {
            Log::info("No se encontraron invitaciones expiradas.");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("Job falló eliminando invitaciones expiradas", [
            'error' => $exception->getMessage()
        ]);
    }
}
