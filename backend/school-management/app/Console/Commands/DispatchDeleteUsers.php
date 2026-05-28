<?php

namespace App\Console\Commands;

use App\Jobs\CleanDeleteUsersJob;
use Illuminate\Console\Command;

class DispatchDeleteUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:dispatch-delete-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Despacha el job que elimina usuarios despues de 30 dias de haber sido eliminados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        CleanDeleteUsersJob::dispatch()->onQueue('default');
        $this->info('CleanDeleteUsersJob despachado');
    }
}
