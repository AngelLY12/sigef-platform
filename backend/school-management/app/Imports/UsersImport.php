<?php

namespace App\Imports;

use App\Core\Application\Services\Admin\AdminUsersServiceFacades;
use App\Models\User;
use App\Notifications\ImportFailedNotification;
use App\Notifications\ImportFinishedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\ImportFailed;

class UsersImport implements ToCollection, ShouldQueue, WithEvents, WithChunkReading
{
    protected AdminUsersServiceFacades $adminService;
    protected User $user;
    private array $importResult = [];
    private string $cacheKey;

    public function __construct(AdminUsersServiceFacades $adminService, User $user)
    {
        $this->adminService = $adminService;
        $this->user = $user;
        $this->cacheKey = 'import_result_' . $user->id . '_' . time();
    }

    public function collection(Collection $collection)
    {
        $rows = $collection->skip(1)
            ->reject(function($row) {
                return collect($row)->every(function($value) {
                    return is_null($value) || trim($value) === '';
                });
            })
            ->values()
            ->toArray();
        $importResponse = $this->adminService->importUsers($rows);
        $this->importResult = $importResponse->toArray();
        Cache::put($this->cacheKey, $this->importResult, now()->addMinutes(10));
    }
    public function registerEvents(): array
    {
        return [
            AfterImport::class => function() {
                $result = Cache::get($this->cacheKey, []);

                $this->user->notify(new ImportFinishedNotification(
                    $result ?: [
                        'summary' => [],
                        'errors' => [],
                        'warnings' => [],
                        'has_errors' => true,
                        'message' => 'El import terminó pero no se pudo generar el resumen.'
                    ]
                ));
                Cache::forget($this->cacheKey);

            },
            ImportFailed::class => function(ImportFailed $event) {
                $this->user->notify(new ImportFailedNotification(
                    $event->getException()->getMessage()
                ));
                Cache::forget($this->cacheKey);
            }
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function getResult(): array{
        return $this->importResult;
    }
}
