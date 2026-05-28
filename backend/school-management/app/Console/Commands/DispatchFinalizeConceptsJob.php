<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FinalizeExpiredConceptsJob;

class DispatchFinalizeConceptsJob extends Command
{
    protected $signature = 'concepts:dispatch-finalize-job';
    protected $description = 'Despacha el job que finaliza los conceptos expirados';

    public function handle()
    {
        FinalizeExpiredConceptsJob::dispatch()->onQueue('default');
        $this->info('FinalizeExpiredConceptsJob despachado.');
    }
}
