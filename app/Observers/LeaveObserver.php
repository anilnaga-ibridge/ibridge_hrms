<?php

namespace App\Observers;

use App\Models\Leave;

class LeaveObserver
{
    public function saving(Leave $leave)
    {
        $company = company();

        // Cannot put in creating, because saving is fired before creating. And we need company id for check bellow
        if ($company && !$company->is_global) {
            $leave->company_id = $company->id;
        }
    }

    public function deleting(Leave $leave)
    {
        // Restore monthly leaves that were marked USED by this leave
        \App\Models\EmployeeMonthlyLeave::where('used_in_leave_id', $leave->id)
            ->update([
                'status' => 'ACTIVE',
                'used_date' => null,
                'used_in_leave_id' => null,
            ]);
    }
}

