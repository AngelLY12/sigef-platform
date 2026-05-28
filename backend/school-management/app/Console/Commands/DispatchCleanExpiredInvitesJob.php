<?php

namespace App\Console\Commands;

use App\Jobs\CleanExpiredInvitesJob;
use Illuminate\Console\Command;

class DispatchCleanExpiredInvitesJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invites:dispatch-clean-expired-invites-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Despacha el job que elimina invitaciones expiradas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        CleanExpiredInvitesJob::dispatch()->onQueue('default');
        $this->info('CleanExpiredInvitesJob despachado');
    }
}
