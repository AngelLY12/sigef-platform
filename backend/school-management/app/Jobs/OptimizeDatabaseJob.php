<?php

namespace App\Jobs;

use App\Core\Application\UseCases\Jobs\OptimizeDatabaseUseCase;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OptimizeDatabaseJob implements ShouldQueue
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
    public function handle(OptimizeDatabaseUseCase $case): void
    {
        $result = $case->execute();

        if ($result->optimized) {
            Log::info('Database optimized successfully', [
                'tables' => $result->tables,
                'fragmentation_mb' => round($result->totalFragmentationBytes / 1024 / 1024, 2),
            ]);
        } else {
            Log::info('Database optimization skipped (no fragmentation detected)');
        }
    }
}
