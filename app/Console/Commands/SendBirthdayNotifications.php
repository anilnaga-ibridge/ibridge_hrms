<?php

namespace App\Console\Commands;

use App\Classes\Notify;
use App\Models\StaffMember;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendBirthdayNotifications extends Command
{
    protected $signature = 'birthday:notify';
    protected $description = 'Send birthday wishes to employees whose birthday is today and notify all other employees using company timezone';

    public function handle()
    {
        $companies = \App\Models\Company::all();

        foreach ($companies as $company) {
            $today = Carbon::now($company->timezone)->startOfDay();
            
            $birthdayUsers = StaffMember::withoutGlobalScope(\App\Scopes\CompanyScope::class)
                ->where('company_id', $company->id)
                ->where('status', 'active')
                ->whereRaw("DATE_FORMAT(dob, '%m-%d') = ?", [$today->format('m-d')])
                ->get();

            foreach ($birthdayUsers as $birthdayUser) {
                // Send birthday wish to the birthday person
                Notify::send('employee_birthday_wish', $birthdayUser);
                $this->info("Birthday wish sent to: {$birthdayUser->name} in company: {$company->name}");

                // Notify all other active employees in the same company
                $otherEmployees = StaffMember::withoutGlobalScope(\App\Scopes\CompanyScope::class)
                    ->where('company_id', $company->id)
                    ->where('status', 'active')
                    ->where('id', '!=', $birthdayUser->id)
                    ->get();

                foreach ($otherEmployees as $employee) {
                    $notificationData = [
                        'birthday_user_id' => $birthdayUser->id,
                        'recipient_id' => $employee->id,
                    ];
                    Notify::send('employee_birthday_reminder', $notificationData);
                }
            }
        }

        $this->info('Birthday notifications processed.');
        return 0;
    }
}
