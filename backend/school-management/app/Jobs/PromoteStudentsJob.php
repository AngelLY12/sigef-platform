<?php

namespace App\Jobs;

use App\Core\Application\UseCases\Jobs\PromoteStudentsUseCase;
use App\Events\StudentsPromotionCompleted;
use App\Events\StudentsPromotionFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PromoteStudentsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private ?int $adminId=null
    )
    {
        //
    }
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping(self::class))->expireAfter(600)
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(PromoteStudentsUseCase $promote): void
    {
        $response = $promote->execute();
        Log::info('Promoción completada', [
            'promoted' => $response->promotedStudents,
            'desactivated' => $response->desactivatedStudents,
            'admin_id' => $this->adminId
        ]);
        if($this->adminId)
        {
            event(new StudentsPromotionCompleted(
                adminId: $this->adminId,
                promotedCount: $response->promotedStudents,
                desactivatedCount: $response->desactivatedStudents
            ));
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("Job falló incrementando semestres", [
            'error' => $exception->getMessage()
        ]);
        if ($this->adminId) {
            event(new StudentsPromotionFailed(
                adminId: $this->adminId,
                error: $exception->getMessage()
            ));
        }
    }
}
