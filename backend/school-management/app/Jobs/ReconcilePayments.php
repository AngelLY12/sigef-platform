<?php

namespace App\Jobs;

use App\Core\Application\Services\Payments\ReconcilePaymentsService;
use App\Core\Application\UseCases\Payments\Reconcile\ReconcilePaymentsBatchUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReconcilePayments implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct()
    {
    }
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(self::class))->expireAfter(600)
        ];
    }

    /**
     * Create a new job instance.
     */

    /**
     * Execute the job.
     * @throws \Throwable
     */
    public function handle(ReconcilePaymentsBatchUseCase $reconcile): void
    {
        $result = $reconcile->execute();
        logger()->info('[ReconcilePayments] Finished', [
            'processed' => $result->processed,
            'updated'   => $result->updated,
            'notified'  => $result->notified,
            'failed'    => $result->failed,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("Job falló reconciliando pagos", [
            'error' => $exception->getMessage()
        ]);
    }
    public static function forCron(): PendingDispatch
    {
        return self::dispatch();
    }
}
