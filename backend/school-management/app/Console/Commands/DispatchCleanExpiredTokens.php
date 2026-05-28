<?php

namespace App\Console\Commands;

use App\Jobs\CleanExpiredTokensJob;
use Illuminate\Console\Command;

class DispatchCleanExpiredTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:dispatch-clean-expired-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Despacha el job que elimina tokens de acceso expirados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        CleanExpiredTokensJob::dispatch()->onQueue('default');
        $this->info('CleanExpiredTokensJob despachado');

    }
}
