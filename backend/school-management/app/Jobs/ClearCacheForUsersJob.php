<?php

namespace App\Jobs;

use App\Core\Domain\Enum\PaymentConcept\PaymentConceptStatus;
use App\Core\Infraestructure\Cache\CacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClearCacheForUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private array $userIds;
    private bool $isConceptUpdated=false;
    private ?PaymentConceptStatus $conceptStatus=null;
    /**
     * Create a new job instance.
     */
    public function __construct(array $userIds, bool $isConceptUpdated=false, ?PaymentConceptStatus $conceptStatus = null)
    {
        $this->userIds = $userIds;
        $this->isConceptUpdated = $isConceptUpdated;
        $this->conceptStatus = $conceptStatus;
    }

    /**
     * Execute the job.
     */
    public function handle(CacheService $cacheService): void
    {
        foreach ($this->userIds as $userId) {
            if($this->isConceptUpdated && $this->conceptStatus) {
                $cacheService->clearCacheWhileConceptChangeStatus($userId,$this->conceptStatus);
            }else{
                $cacheService->clearStudentCache($userId);
            }
        }
    }
    public static function forConceptStatus(array $userIds, PaymentConceptStatus $status): PendingDispatch
    {
        return self::dispatch($userIds, true, $status);
    }
    public static function forStudents(array $userIds): PendingDispatch
    {
        return self::dispatch($userIds, false, null);
    }
    public static function forUsers(array $userIds, bool $isConceptUpdated = false, ?PaymentConceptStatus $status = null): PendingDispatch
    {
        return self::dispatch($userIds, $isConceptUpdated, $status);
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("Job falló limpiando cache de estudiantes: {$this->userIds}", [
            'error' => $exception->getMessage()
        ]);
    }
}
