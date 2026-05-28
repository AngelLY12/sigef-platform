<?php

namespace App\Console\Commands;

use App\Jobs\CleanExpiredRefreshTokens;
use Illuminate\Console\Command;

class DispatchCleanExpiredRefreshTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tokens:dispatch-clean-expired-refresh-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Despacha el job que elimina tokens refresh expirados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        CleanExpiredRefreshTokens::dispatch()->onQueue('default');
        $this->info('CleanExpiredRefreshTokensJob despachado');
    }
}
