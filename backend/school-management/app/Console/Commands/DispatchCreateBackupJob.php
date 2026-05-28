<?php

namespace App\Console\Commands;

use App\Jobs\CreateBackupJob;
use Illuminate\Console\Command;

class DispatchCreateBackupJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:dispatch-create-backup-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ejecuta el comando que crea el backup de la base de datos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        CreateBackupJob::dispatch()->onQueue('maintenance-heavy');
        $this->info('CreateBackupJob despachado');
    }
}
