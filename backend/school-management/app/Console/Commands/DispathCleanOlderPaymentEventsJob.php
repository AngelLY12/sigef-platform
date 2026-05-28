<?php

namespace App\Console\Commands;

use App\Jobs\CleanOlderPaymentEventsJob;
use Illuminate\Console\Command;

class DispathCleanOlderPaymentEventsJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:dispath-clean-older-payment-events-job';

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
        CleanOlderPaymentEventsJob::dispatch()->onQueue('maintenance-heavy');
        $this->info('CleanOlderPaymentEventsJob dispatched');
    }
}
