<?php

namespace App\Console\Commands\EnterpriseTasks;

use Illuminate\Console\Command;
use App\Jobs\EnterpriseTasks\RecalculateProductivityJob;

class EnterpriseRecalculateProductivity extends Command
{
    protected $signature = 'enterprise:recalculate-productivity';
    protected $description = 'Recalculate user monthly productivity scores and rankings';

    public function handle(): void
    {
        RecalculateProductivityJob::dispatchSync();
        $this->info('Productivity scores and rankings recalculated successfully.');
    }
}
