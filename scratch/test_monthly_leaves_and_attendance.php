<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\LeaveType;
use App\Models\EmployeeSpecificLeaveCount;
use App\Models\EmployeeMonthlyLeave;
use App\Models\Leave;
use App\Models\Attendance;
use App\Classes\CommonHrm;
use App\Classes\RaviCommonHrm;
use Carbon\Carbon;

// 1. Find user
$user = User::where('name', 'like', '%Jerrold Runte%')->first();
if (!$user) {
    echo "Jerrold Runte not found, using first user.\n";
    $user = User::first();
}
if (!$user) {
    echo "No users found in database!\n";
    exit(1);
}

echo "Testing user: {$user->name} (ID: {$user->id})\n";

// 2. Resolve Leave Type
$leaveType = LeaveType::where('name', 'Monthly Leave')->first();
if (!$leaveType) {
    echo "Creating 'Monthly Leave' type...\n";
    $leaveType = new LeaveType();
    $leaveType->name = 'Monthly Leave';
    $leaveType->company_id = $user->company_id;
    $leaveType->is_monthly_leave = 1;
    $leaveType->count_type = 'employee_specific';
    $leaveType->max_leaves_per_month = 5;
    $leaveType->save();
}

$originalCountType = $leaveType->count_type;
$originalMaxLeaves = $leaveType->max_leaves_per_month;

// --- Test 2.1: getEffectiveLeaveQuota under employee_specific ---
echo "\n--- Test 2.1: getEffectiveLeaveQuota under employee_specific ---\n";
$leaveType->count_type = 'employee_specific';
$leaveType->max_leaves_per_month = 4;
$leaveType->save();

// Clean up existing specific records
EmployeeSpecificLeaveCount::where('user_id', $user->id)
    ->where('leave_type_id', $leaveType->id)
    ->delete();

// case A: no record exists -> should return 0 maxLeavesPerMonth
$quotaNoRecordCommon = CommonHrm::getEffectiveLeaveQuota($user->id, $leaveType);
$quotaNoRecordRavi = RaviCommonHrm::getEffectiveLeaveQuota($user->id, $leaveType);
echo "CommonHrm quota (no record): " . json_encode($quotaNoRecordCommon) . "\n";
echo "RaviCommonHrm quota (no record): " . json_encode($quotaNoRecordRavi) . "\n";

if ($quotaNoRecordCommon['maxLeavesPerMonth'] === 0 && $quotaNoRecordRavi['maxLeavesPerMonth'] === 0) {
    echo "Case A Passed: No record yields 0 cap.\n";
} else {
    echo "Case A FAILED!\n";
}

// case B: record exists with max_leaves_per_month = 2 -> should return 2
$specRecord = new EmployeeSpecificLeaveCount();
$specRecord->user_id = $user->id;
$specRecord->leave_type_id = $leaveType->id;
$specRecord->total_leaves = 10;
$specRecord->max_leaves_per_month = 2;
$specRecord->monthly_leave_expiry_cycle = 3;
$specRecord->save();

$quotaRecordCommon = CommonHrm::getEffectiveLeaveQuota($user->id, $leaveType);
$quotaRecordRavi = RaviCommonHrm::getEffectiveLeaveQuota($user->id, $leaveType);
echo "CommonHrm quota (with record=2): " . json_encode($quotaRecordCommon) . "\n";
echo "RaviCommonHrm quota (with record=2): " . json_encode($quotaRecordRavi) . "\n";

if ($quotaRecordCommon['maxLeavesPerMonth'] === 2 && $quotaRecordRavi['maxLeavesPerMonth'] === 2) {
    echo "Case B Passed: Specific record cap = 2 used.\n";
} else {
    echo "Case B FAILED!\n";
}

// case C: record exists with max_leaves_per_month = null -> should return 0 (no fallback to global max_leaves_per_month = 4)
$specRecord->max_leaves_per_month = null;
$specRecord->save();

$quotaNullRecordCommon = CommonHrm::getEffectiveLeaveQuota($user->id, $leaveType);
$quotaNullRecordRavi = RaviCommonHrm::getEffectiveLeaveQuota($user->id, $leaveType);
echo "CommonHrm quota (with record=null): " . json_encode($quotaNullRecordCommon) . "\n";
echo "RaviCommonHrm quota (with record=null): " . json_encode($quotaNullRecordRavi) . "\n";

if ($quotaNullRecordCommon['maxLeavesPerMonth'] === 0 && $quotaNullRecordRavi['maxLeavesPerMonth'] === 0) {
    echo "Case C Passed: Null specific record cap yields 0 (no fallback).\n";
} else {
    echo "Case C FAILED!\n";
}

// --- Test 2.2: processEmployeeMonthlyLeaves and Expiration ---
echo "\n--- Test 2.2: processEmployeeMonthlyLeaves and Expiration ---\n";
// Clean up old monthly leaves
EmployeeMonthlyLeave::where('employee_id', $user->id)->delete();

// Set up specific record max_leaves_per_month to 2
$specRecord->max_leaves_per_month = 2;
$specRecord->save();

// Let's run processEmployeeMonthlyLeaves
CommonHrm::processEmployeeMonthlyLeaves($user);

$activeCount = EmployeeMonthlyLeave::where('employee_id', $user->id)
    ->where('status', 'ACTIVE')
    ->count();
echo "ACTIVE credits after initial run: {$activeCount}\n";

// Let's manually create an old ACTIVE credit dated 5 months ago
$oldCredit = new EmployeeMonthlyLeave();
$oldCredit->company_id = $user->company_id;
$oldCredit->employee_id = $user->id;
$oldCredit->credited_date = Carbon::now()->subMonths(5)->startOfMonth();
$oldCredit->status = 'ACTIVE';
$oldCredit->save();

echo "Added old credit ID {$oldCredit->id} (date: {$oldCredit->credited_date->toDateString()}). Running process again...\n";
CommonHrm::processEmployeeMonthlyLeaves($user);

$checkOld = EmployeeMonthlyLeave::find($oldCredit->id);
echo "Old credit status after process: {$checkOld->status}\n";

if ($checkOld->status === 'EXPIRED') {
    echo "Expiration Test Passed: Old monthly credits expired even when current month is credited.\n";
} else {
    echo "Expiration Test FAILED!\n";
}

// --- Test 2.3: updateLeaveStatus and Attendance check ---
echo "\n--- Test 2.3: updateLeaveStatus and Attendance check ---\n";
// Reset active monthly leaves
EmployeeMonthlyLeave::where('employee_id', $user->id)->delete();

$newCredit = new EmployeeMonthlyLeave();
$newCredit->company_id = $user->company_id;
$newCredit->employee_id = $user->id;
$newCredit->credited_date = Carbon::now()->startOfMonth();
$newCredit->status = 'ACTIVE';
$newCredit->save();

// Clear existing attendance for today
Attendance::where('user_id', $user->id)->whereDate('date', Carbon::today())->delete();

// Case A: Work attendance exists
$att = new Attendance();
$att->company_id = $user->company_id;
$att->user_id = $user->id;
$att->date = Carbon::today();
$att->is_leave = 0;
$att->save();

$leave = new Leave();
$leave->company_id = $user->company_id;
$leave->user_id = $user->id;
$leave->leave_type_id = $leaveType->id;
$leave->start_date = Carbon::today();
$leave->end_date = Carbon::today();
$leave->is_half_day = 0;
$leave->is_paid = 1;
$leave->reason = 'Test Reason';
$leave->status = 'approved';
$leave->save();

echo "Created work attendance (is_leave=0) and leave application.\n";
CommonHrm::updateLeaveStatus($leave->id);

$checkCredit = EmployeeMonthlyLeave::find($newCredit->id);
echo "Credit status after updateLeaveStatus (with work attendance): {$checkCredit->status}\n";

if ($checkCredit->status === 'ACTIVE') {
    echo "Attendance Check Case A Passed: Monthly credit not consumed because work attendance exists.\n";
} else {
    echo "Attendance Check Case A FAILED!\n";
}

// Case B: No work attendance exists
$att->delete();
echo "Deleted work attendance.\n";

// Reset credit status to ACTIVE
$newCredit->status = 'ACTIVE';
$newCredit->used_in_leave_id = null;
$newCredit->used_date = null;
$newCredit->save();

CommonHrm::updateLeaveStatus($leave->id);

$checkCredit2 = EmployeeMonthlyLeave::find($newCredit->id);
echo "Credit status after updateLeaveStatus (without work attendance): {$checkCredit2->status}\n";

if ($checkCredit2->status === 'USED') {
    echo "Attendance Check Case B Passed: Monthly credit consumed.\n";
} else {
    echo "Attendance Check Case B FAILED!\n";
}

// Clean up
$leave->delete();
$specRecord->delete();
$leaveType->count_type = $originalCountType;
$leaveType->max_leaves_per_month = $originalMaxLeaves;
$leaveType->save();
EmployeeMonthlyLeave::where('employee_id', $user->id)->delete();
Attendance::where('user_id', $user->id)->whereDate('date', Carbon::today())->delete();
echo "\nAll tests completed!\n";
