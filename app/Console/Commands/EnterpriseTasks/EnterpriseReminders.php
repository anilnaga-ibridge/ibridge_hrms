<?php

namespace App\Console\Commands\EnterpriseTasks;

use Illuminate\Console\Command;
use App\Jobs\EnterpriseTasks\ReminderJob;

class EnterpriseReminders extends Command
{
    protected $signature = 'enterprise:reminders';
    protected $description = 'Scan and trigger active task reminders';

    public function handle(): void
    {
        ReminderJob::dispatchSync();
        $this->info('Active reminders processed successfully.');
    }
}
