<?php

namespace App\Console\Commands\EnterpriseTasks;

use Illuminate\Console\Command;
use App\Jobs\EnterpriseTasks\WeeklySummaryJob;

class EnterpriseWeeklySummary extends Command
{
    protected $signature = 'enterprise:weekly-summary';
    protected $description = 'Send weekly summary reports to users';

    public function handle(): void
    {
        WeeklySummaryJob::dispatchSync();
        $this->info('Weekly summaries generated successfully.');
    }
}
