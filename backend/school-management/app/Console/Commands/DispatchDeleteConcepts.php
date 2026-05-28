<?php

namespace App\Console\Commands;

use App\Jobs\CleanDeleteConceptsJob;
use Illuminate\Console\Command;

class DispatchDeleteConcepts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'concepts:dispatch-delete-concepts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Despacha el job que elimina conceptos de pago eliminados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        CleanDeleteConceptsJob::dispatch()->onQueue('default');
        $this->info('CleanDeleteConceptsJob despachado');
    }
}
