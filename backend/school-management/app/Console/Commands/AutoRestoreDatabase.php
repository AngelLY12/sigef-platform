<?php

namespace App\Console\Commands;

use App\Jobs\AutoRestoreDatabaseJob;
use Illuminate\Console\Command;

class AutoRestoreDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:auto-restore';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restaura automáticamente la última copia de seguridad si la base de datos está vacía o inaccesible.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        AutoRestoreDatabaseJob::dispatch()->onQueue('maintenance-heavy');
        $this->info('AutoRestoreDatabaseJob despachado');
    }
}
