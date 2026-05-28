<?php

namespace App\Console\Commands;

use App\Jobs\PromoteStudentsJob;
use Illuminate\Console\Command;

class DispatchPromoteStudentsJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dispatch-promote-students-job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el semestre de los estudiantes y da de baja los que sobrepasan el limite';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        PromoteStudentsJob::dispatch()->onQueue('maintenance-heavy');
        $this->info('PromoteStudentsJob despachado.');
    }
}
