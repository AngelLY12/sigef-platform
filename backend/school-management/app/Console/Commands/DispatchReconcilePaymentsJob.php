<?php

namespace App\Console\Commands;

use App\Jobs\ReconcilePayments;
use Illuminate\Console\Command;

class DispatchReconcilePaymentsJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:dispatch-reconcile-payments-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reconcilia pagos con Stripe';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ReconcilePayments::forCron()->onQueue('maintenance-heavy');
        $this->info('ReconcilePaymentsJob dispatched');
    }
}
