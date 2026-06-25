<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Leave;
use App\Models\LeaveType;
use App\Models\User;
use App\Classes\CommonHrm;
use App\Models\EmployeeMonthlyLeave;

// 1. Find user "Jerrold Runte"
$user = User::where('name', 'like', '%Jerrold Runte%')->first();
if (!$user) {
    echo "User not found\n";
    exit(1);
}

// Reset all leaves for this user to ACTIVE first to start clean
EmployeeMonthlyLeave::where('employee_id', $user->id)
    ->update([
        'status' => 'ACTIVE',
        'used_date' => null,
        'used_in_leave_id' => null,
    ]);

// Ensure the user has active leaves
$activeCountBefore = EmployeeMonthlyLeave::where('employee_id', $user->id)
    ->where('status', 'ACTIVE')
    ->count();

echo "User: " . $user->name . " (ID: " . $user->id . ")\n";
echo "ACTIVE monthly leaves before: " . $activeCountBefore . "\n";

if ($activeCountBefore == 0) {
    echo "No ACTIVE monthly leaves to test FIFO with. Adding one...\n";
    $eml = new EmployeeMonthlyLeave();
    $eml->company_id = $user->company_id;
    $eml->employee_id = $user->id;
    $eml->credited_date = \Carbon\Carbon::now()->startOfMonth();
    $eml->status = 'ACTIVE';
    $eml->save();
    $activeCountBefore = 1;
}

// Get the oldest active monthly leave
$oldestActiveBefore = EmployeeMonthlyLeave::where('employee_id', $user->id)
    ->where('status', 'ACTIVE')
    ->orderBy('credited_date')
    ->first();

echo "Oldest ACTIVE monthly leave credited date: " . $oldestActiveBefore->credited_date->format('Y-m-d') . "\n";

// 2. Create a Leave application
$leaveType = LeaveType::where('name', 'Monthly Leave')->first();
if (!$leaveType) {
    echo "Monthly Leave type not found\n";
    exit(1);
}

$leave = new Leave();
$leave->company_id = $user->company_id;
$leave->user_id = $user->id;
$leave->leave_type_id = $leaveType->id;
$leave->start_date = \Carbon\Carbon::now();
$leave->end_date = \Carbon\Carbon::now();
$leave->is_half_day = 0;
$leave->is_paid = 1;
$leave->reason = 'Test FIFO monthly leave';
$leave->status = 'approved';
$leave->save();

echo "Leave application created successfully (ID: {$leave->id}).\n";

// 3. Process the leave (which simulates updateLeaveStatus)
CommonHrm::updateLeaveStatus($leave->id);

// 4. Verify that the oldest active monthly leave is now USED
$checkOldest = EmployeeMonthlyLeave::find($oldestActiveBefore->id);
echo "Oldest monthly leave status after processing leave: " . $checkOldest->status . "\n";
echo "Oldest monthly leave used_in_leave_id: " . ($checkOldest->used_in_leave_id ?: 'NULL') . "\n";

if ($checkOldest->status === 'USED' && $checkOldest->used_in_leave_id == $leave->id) {
    echo "FIFO consumption test passed!\n";
} else {
    echo "FIFO consumption test FAILED!\n";
}

// 5. Delete the leave (manually invoke observer since we are running in Console/CLI)
echo "Manually invoking LeaveObserver::deleting...\n";
$observer = new \App\Observers\LeaveObserver();
$observer->deleting($leave);

$leave->delete();
echo "Leave application deleted.\n";

$checkOldestAfterDelete = EmployeeMonthlyLeave::find($oldestActiveBefore->id);
echo "Raw attributes: " . json_encode($checkOldestAfterDelete->getAttributes()) . "\n";
echo "Oldest monthly leave status after deleting leave: " . $checkOldestAfterDelete->status . "\n";
echo "Oldest monthly leave used_in_leave_id after deleting leave: " . ($checkOldestAfterDelete->used_in_leave_id ?: 'NULL') . "\n";

if ($checkOldestAfterDelete->status === 'ACTIVE' && is_null($checkOldestAfterDelete->getAttributes()['used_in_leave_id'])) {
    echo "Rollback test passed!\n";
} else {
    echo "Rollback test FAILED!\n";
}
