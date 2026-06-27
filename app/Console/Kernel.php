<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('birthday:notify')->dailyAt('08:00');
        $schedule->command('holiday:notify')->dailyAt('18:00');
        $schedule->command('hrm:credit-monthly-leaves')->monthly();
        $schedule->command('performance:calculate')->monthly();

        if (app_type() == 'saas') {
            $schedule->command(\App\SuperAdmin\Commands\UpdateLicenseExpiry::class)->daily();
            $schedule->command(\App\SuperAdmin\Commands\NotifyLicenseExpiryPre::class)->daily();
        }

        // Enterprise Tasks Scheduled Commands
        $schedule->command('enterprise:check-overdue')->daily();
        $schedule->command('enterprise:reminders')->everyMinute();
        $schedule->command('enterprise:recurring-tasks')->daily();
        $schedule->command('enterprise:daily-summary')->dailyAt('18:00');
        $schedule->command('enterprise:weekly-summary')->weeklyOn(5, '17:00');
        $schedule->command('enterprise:archive-tasks')->weekly();
        $schedule->command('enterprise:recalculate-metrics')->hourly();
        $schedule->command('enterprise:recalculate-productivity')->monthly();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
