<?php

namespace App\Console\Commands;

use App\Classes\Notify;
use App\Models\Holiday;
use App\Models\StaffMember;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendHolidayNotifications extends Command
{
    protected $signature = 'holiday:notify';
    protected $description = 'Notify employees of tomorrow\'s holidays using company timezone';

    public function handle()
    {
        $companies = \App\Models\Company::all();

        foreach ($companies as $company) {
            $tomorrow = Carbon::tomorrow($company->timezone);
            
            $holiday = Holiday::withoutGlobalScope(\App\Scopes\CompanyScope::class)
                ->where('company_id', $company->id)
                ->whereDate('date', $tomorrow)
                ->first();

            if ($holiday) {
                $activeEmployees = StaffMember::withoutGlobalScope(\App\Scopes\CompanyScope::class)
                    ->where('company_id', $company->id)
                    ->where('status', 'active')
                    ->get();

                foreach ($activeEmployees as $employee) {
                    $notificationData = [
                        'holiday_id' => $holiday->id,
                        'recipient_id' => $employee->id,
                    ];
                    Notify::send('holiday_reminder', $notificationData);
                }

                $this->info("Holiday notifications sent for tomorrow's holiday: '{$holiday->name}' in company: {$company->name}");
            }
        }

        $this->info('Holiday notifications processed.');
        return 0;
    }
}
