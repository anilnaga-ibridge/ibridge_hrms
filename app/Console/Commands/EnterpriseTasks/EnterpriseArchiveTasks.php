<?php

namespace App\Console\Commands\EnterpriseTasks;

use Illuminate\Console\Command;
use App\Jobs\EnterpriseTasks\ArchiveOldTasksJob;

class EnterpriseArchiveTasks extends Command
{
    protected $signature = 'enterprise:archive-tasks';
    protected $description = 'Archive completed/cancelled tasks older than 30 days';

    public function handle(): void
    {
        ArchiveOldTasksJob::dispatchSync();
        $this->info('Old tasks archived successfully.');
    }
}
