<?php

namespace App\Console\Commands\EnterpriseTasks;

use Illuminate\Console\Command;
use App\Jobs\EnterpriseTasks\RecurringTaskJob;

class EnterpriseRecurringTasks extends Command
{
    protected $signature = 'enterprise:recurring-tasks';
    protected $description = 'Generate new tasks for recurring cycles';

    public function handle(): void
    {
        RecurringTaskJob::dispatchSync();
        $this->info('Recurring tasks processed successfully.');
    }
}
