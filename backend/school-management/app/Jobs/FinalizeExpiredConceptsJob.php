<?php
namespace App\Jobs;

use App\Core\Application\UseCases\Jobs\FinalizePaymentConceptsUseCase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FinalizeExpiredConceptsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    public function handle(FinalizePaymentConceptsUseCase $finalize)
    {
        $result=$finalize->execute();
        if($result>0){
            Log::info("Se finalizaron exitosamente: $result conceptos");
        }
        else{
            Log::info("No se finalizo ningun concepto");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("Job falló finalizando conceptos", [
            'error' => $exception->getMessage()
        ]);
    }
}
