<?php

namespace App\Console\Commands\EnterpriseTasks;

use Illuminate\Console\Command;
use App\Jobs\EnterpriseTasks\RecalculateMetricsJob;

class EnterpriseRecalculateMetrics extends Command
{
    protected $signature = 'enterprise:recalculate-metrics';
    protected $description = 'Recalculate all project and task metrics';

    public function handle(): void
    {
        RecalculateMetricsJob::dispatchSync();
        $this->info('Metrics recalculated successfully.');
    }
}
