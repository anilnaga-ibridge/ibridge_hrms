<?php

namespace App\Console\Commands\EnterpriseTasks;

use Illuminate\Console\Command;
use App\Jobs\EnterpriseTasks\CheckOverdueTasksJob;

class EnterpriseCheckOverdue extends Command
{
    protected $signature = 'enterprise:check-overdue';
    protected $description = 'Scan and trigger alerts for overdue tasks';

    public function handle(): void
    {
        CheckOverdueTasksJob::dispatchSync();
        $this->info('Overdue tasks checked successfully.');
    }
}
