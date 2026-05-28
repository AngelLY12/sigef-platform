<?php

namespace App\Console\Commands;

use App\Jobs\OptimizeDatabaseJob;
use Illuminate\Console\Command;

class DispatchOptimizeDatabaseJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dispatch-optimize-database-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        OptimizeDatabaseJob::dispatch()->onQueue('maintenance-heavy');
        $this->info('OptimizeDatabaseJob despachado.');

    }
}
