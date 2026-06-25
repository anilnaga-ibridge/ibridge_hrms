<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Classes\CommonHrm;

class CreditMonthlyLeaves extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hrm:credit-monthly-leaves {--employee_id= : Decoded ID of the employee to run for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Credit monthly leaves for active employees and handle carry forward / expiry rules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $employeeId = $this->option('employee_id');

        if ($employeeId) {
            $employee = User::where('user_type', 'staff_members')
                ->where('status', 'active')
                ->find($employeeId);

            if (!$employee) {
                $this->error("Employee with ID {$employeeId} not found or is inactive.");
                return 1;
            }

            $this->info("Processing monthly leaves for employee: {$employee->name}...");
            CommonHrm::processEmployeeMonthlyLeaves($employee);
        } else {
            $employees = User::where('user_type', 'staff_members')
                ->where('status', 'active')
                ->get();

            $this->info("Processing monthly leaves for " . $employees->count() . " active employees...");

            foreach ($employees as $employee) {
                CommonHrm::processEmployeeMonthlyLeaves($employee);
            }
        }

        $this->info('Monthly leave processing completed successfully!');
        return 0;
    }
}
