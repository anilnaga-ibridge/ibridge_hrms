<?php

namespace App\Console\Commands\EnterpriseTasks;

use Illuminate\Console\Command;
use App\Jobs\EnterpriseTasks\DailySummaryJob;

class EnterpriseDailySummary extends Command
{
    protected $signature = 'enterprise:daily-summary';
    protected $description = 'Send daily summary logs to users';

    public function handle(): void
    {
        DailySummaryJob::dispatchSync();
        $this->info('Daily summaries generated successfully.');
    }
}
