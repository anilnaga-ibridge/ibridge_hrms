<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Http\Requests\Api\EmployeeMonthlyLeave\ProcessRequest;
use App\Models\EmployeeMonthlyLeave;
use App\Models\EmployeeSpecificLeaveCount;
use App\Models\User;
use App\Classes\CommonHrm;
use Carbon\Carbon;
use Examyou\RestAPI\ApiResponse;
use Examyou\RestAPI\Exceptions\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class EmployeeMonthlyLeaveController extends ApiBaseController
{
    protected $model = EmployeeMonthlyLeave::class;

    protected function modifyIndex($query)
    {
        $loggedUser = user();
        $request = request();

        $isSelfRoute = $request->routeIs('api.self.*') || $request->is('api/v1/self/*') || $request->is('*/self/*');

        if ($isSelfRoute) {
            $query = $query->where('employee_monthly_leave.employee_id', $loggedUser->id);
        } else {
            // Check if admin or has leaves_view permission
            if ($loggedUser->ability('admin', 'leaves_view')) {
                if ($request->has('user_id') && $request->user_id != "") {
                    $employeeId = $this->getIdFromHash($request->user_id);
                    $query = $query->where('employee_monthly_leave.employee_id', $employeeId);
                }
            } else {
                // Non-admin can only see their own leaves
                $query = $query->where('employee_monthly_leave.employee_id', $loggedUser->id);
            }
        }

        $query = $query->orderBy('credited_date', 'desc');

        return $query;
    }

    /**
     * Summary stats for a single employee (or the logged-in user for self view).
     * Returns a structured breakdown: current-month leave, carry-forward leaves,
     * recent expired leaves, and a contextual info message.
     * GET /employee-monthly-leaves/summary?user_id=<xid>
     * GET /self/employee-monthly-leaves/summary
     */
    public function summary(Request $request)
    {
        $loggedUser = user();
        $companyId = company()->id;

        $query = EmployeeMonthlyLeave::where('company_id', $companyId);

        $isSelfRoute = $request->routeIs('api.self.*') || $request->is('api/v1/self/*') || $request->is('*/self/*');

        if ($isSelfRoute) {
            $query->where('employee_id', $loggedUser->id);
            $employee = $loggedUser;
        } else {
            if ($loggedUser->ability('admin', 'leaves_view')) {
                if ($request->has('user_id') && $request->user_id != '') {
                    $employeeId = $this->getIdFromHash($request->user_id);
                    $query->where('employee_id', $employeeId);
                    $employee = User::find($employeeId);
                } else {
                    $query->where('employee_id', $loggedUser->id);
                    $employee = $loggedUser;
                }
            } else {
                $query->where('employee_id', $loggedUser->id);
                $employee = $loggedUser;
            }
        }

        if (!$employee) {
            throw new ApiException("Employee not found.");
        }

        $currentMonthStart = Carbon::now()->startOfMonth();
        $nextCreditDate = $currentMonthStart->copy()->addMonth()->format('d M Y');
        $nextMonthLabel = $currentMonthStart->copy()->addMonth()->format('F Y');

        // All ACTIVE leaves, oldest first (so current month is last)
        $activeLeaves = (clone $query)->where('status', 'ACTIVE')
            ->orderBy('credited_date', 'asc')
            ->get();

        // Separate into current-month vs carry-forward
        $currentMonthLeaveLabel = null;
        $carryForwardLabels = [];
        $currentMonthActiveCount = 0;

        foreach ($activeLeaves as $leave) {
            $label = $leave->credited_date->format('F Y');
            if ($leave->credited_date->isSameMonth($currentMonthStart)) {
                $currentMonthLeaveLabel = $label;
                $currentMonthActiveCount++;
            } else {
                $carryForwardLabels[] = $label;
            }
        }

        // 6 most recent expired leaves
        $expiredLeaves = (clone $query)->where('status', 'EXPIRED')
            ->orderByDesc('credited_date')
            ->limit(6)
            ->get();

        $expiredLabels = $expiredLeaves->map(fn($l) => $l->credited_date->format('F Y'))->values()->toArray();

        $usedCount = (clone $query)->where('status', 'USED')->count();
        $expiredCount = (clone $query)->where('status', 'EXPIRED')->count();
        $activeCount = $activeLeaves->count();
        $carryCount = count($carryForwardLabels);

        $lastUsedLeave = (clone $query)->where('status', 'USED')->orderByDesc('used_date')->first();
        $lastUsedDate = ($lastUsedLeave && $lastUsedLeave->used_date)
            ? $lastUsedLeave->used_date->format('d M Y')
            : null;

        // Fetch dynamic limit based on Monthly Leave type configuration
        $leaveType = \App\Models\LeaveType::where('company_id', $companyId)
            ->get()
            ->first(fn($t) => $t->isMonthlyLeave());

        $limit = 3; // Default global value
        $maxLeavesPerMonth = 1;
        if ($leaveType) {
            // Use CommonHrm to get the effective quota for the specific employee
            $quota = CommonHrm::getEffectiveLeaveQuota($employee->id, $leaveType);
            $limit = $quota['monthlyLeaveExpiryCycle'];
            $maxLeavesPerMonth = $quota['maxLeavesPerMonth'] > 0 ? $quota['maxLeavesPerMonth'] : ($leaveType->max_leaves_per_month > 0 ? $leaveType->max_leaves_per_month : 1);
        }

        // Build Cycle Breakdown (Current Month, Previous Month, and older months up to the limit)
        $cycleDates = [];
        $cycleDates['current'] = $currentMonthStart->toDateString();
        for ($i = 1; $i < $limit; $i++) {
            $key = $i === 1 ? 'prev' : 'prev' . $i;
            $cycleDates[$key] = $currentMonthStart->copy()->subMonths($i)->toDateString();
        }

        // Fetch user joining/created date for context
        $joiningDate = $employee->joining_date
            ? Carbon::parse($employee->joining_date)
            : Carbon::parse($employee->created_at);

        $cycleLeaves = EmployeeMonthlyLeave::where('employee_id', $employee->id)
            ->whereIn(DB::raw('DATE(credited_date)'), array_values($cycleDates))
            ->get()
            ->keyBy(fn($l) => Carbon::parse($l->credited_date)->toDateString());

        $cycleBreakdown = [];
        $index = 0;
        foreach ($cycleDates as $key => $dateStr) {
            $date = Carbon::parse($dateStr);
            $monthLabel = $date->format('F Y');
            $leave = $cycleLeaves->get($dateStr);

            if ($leave) {
                $status = $leave->status;
                if ($status === 'ACTIVE') {
                    $statusText = ($index === 0) ? 'Active (Current Month)' : 'Active (Carried Forward)';
                } else if ($status === 'USED') {
                    $statusText = 'Used';
                } else {
                    $statusText = 'Expired';
                }
            } else {
                if ($date->lt($joiningDate->startOfMonth())) {
                    $statusText = 'Not Credited (Joined after)';
                } else {
                    $statusText = 'Not Credited';
                }
            }

            if ($index === 0) {
                $label = 'Current Month';
            } elseif ($index === 1) {
                $label = 'Previous Month';
            } else {
                if ($index === 2) {
                    $label = 'Prev 2nd Month';
                } elseif ($index === 3) {
                    $label = 'Prev 3rd Month';
                } else {
                    $label = 'Prev ' . $index . 'th Month';
                }
            }

            $cycleBreakdown[$key] = [
                'month_name' => $monthLabel,
                'label' => $label,
                'status' => $leave ? $leave->status : 'NOT_CREDITED',
                'status_text' => $statusText,
                'used_date' => ($leave && $leave->used_date) ? $leave->used_date->format('d M Y') : null,
            ];
            $index++;
        }

        // Build a contextual, human-readable info message
        if ($activeCount === 0) {
            $infoMessage = "You have no active monthly leaves at the moment. Your next leave will be credited on {$nextCreditDate}.";
        } elseif ($carryCount === 0) {
            $pluralLeaves = $currentMonthActiveCount === 1 ? 'leave' : 'leaves';
            $infoMessage = "You have {$currentMonthActiveCount} {$pluralLeaves} for the current month ({$currentMonthStart->format('F Y')}). No leaves have been carried forward from previous months.";
        } else {
            $carryMonths = implode(', ', $carryForwardLabels);
            $plural = $carryCount === 1 ? 'leave' : 'leaves';
            $infoMessage = "You currently have {$carryCount} carried-forward {$plural} from previous months ({$carryMonths}). "
                . "If these are not used before {$nextCreditDate}, all {$activeCount} active leaves will expire "
                . "and only the {$nextMonthLabel} leaves will remain active.";
        }

        return ApiResponse::make(null, [
            'active_count' => $activeCount,
            'used_count' => $usedCount,
            'expired_count' => $expiredCount,
            'current_month_leave' => $currentMonthLeaveLabel,
            'carry_forward_leaves' => $carryForwardLabels,
            'expired_leaves' => $expiredLabels,
            'next_credit_date' => $nextCreditDate,
            'last_used_date' => $lastUsedDate,
            'info_message' => $infoMessage,
            'cycle_breakdown' => $cycleBreakdown,
            'monthly_quota' => $maxLeavesPerMonth,
        ]);
    }

    /**
     * Per-employee aggregated summary for the admin overview table.
     * GET /employee-monthly-leaves/employee-summary
     */
    public function employeeSummary(Request $request)
    {
        $loggedUser = user();
        $companyId = company()->id;

        $isSelfRoute = $request->routeIs('api.self.*') || $request->is('api/v1/self/*') || $request->is('*/self/*');

        $employeeId = null;
        if ($isSelfRoute) {
            $employeeId = $loggedUser->id;
        } else {
            if (!$loggedUser->ability('admin', 'leaves_view')) {
                throw new ApiException("Not have valid permission");
            }
            if ($request->has('user_id') && $request->user_id != '') {
                $employeeId = $this->getIdFromHash($request->user_id);
            }
        }

        $query = DB::table('employee_monthly_leave as ml')
            ->join('users as u', 'u.id', '=', 'ml.employee_id')
            ->where('ml.company_id', $companyId)
            ->where('u.user_type', 'staff_members')
            ->where('u.status', 'active');

        if ($employeeId !== null) {
            $query = $query->where('ml.employee_id', $employeeId);
        }

        $results = $query->select([
                'u.id   as employee_id',
                'u.name as employee_name',
                DB::raw("SUM(CASE WHEN ml.status = 'ACTIVE'  THEN 1 ELSE 0 END) as active_count"),
                DB::raw("SUM(CASE WHEN ml.status = 'USED'    THEN 1 ELSE 0 END) as used_count"),
                DB::raw("SUM(CASE WHEN ml.status = 'EXPIRED' THEN 1 ELSE 0 END) as expired_count"),
            ])
            ->groupBy('u.id', 'u.name')
            ->orderBy('u.name')
            ->get();

        $data = $results->map(function ($row) {
            return [
                'xid' => Hashids::encode($row->employee_id),
                'employee_name' => $row->employee_name,
                'active_count' => (int) $row->active_count,
                'used_count' => (int) $row->used_count,
                'expired_count' => (int) $row->expired_count,
            ];
        });

        return ApiResponse::make(null, $data->values()->toArray());
    }

    public function process(ProcessRequest $request)
    {
        $loggedUser = user();

        // Check permissions
        if (!$loggedUser->ability('admin', 'leaves_edit')) {
            throw new ApiException("Not have valid permission");
        }

        if ($request->has('user_id') && $request->user_id != "") {
            $employeeId = $this->getIdFromHash($request->user_id);
            $employee = User::where('user_type', 'staff_members')
                ->where('status', 'active')
                ->find($employeeId);

            if (!$employee) {
                throw new ApiException("Active employee not found");
            }

            CommonHrm::processEmployeeMonthlyLeaves($employee);
        } else {
            $employees = User::where('user_type', 'staff_members')
                ->where('status', 'active')
                ->get();

            foreach ($employees as $employee) {
                CommonHrm::processEmployeeMonthlyLeaves($employee);
            }
        }

        return ApiResponse::make('Monthly leaves processed successfully', []);
    }

    /**
     * GET /employee-monthly-leaves/settings
     * Returns the monthly leave type configuration and per-employee settings.
     */
    public function settings()
    {
        $loggedUser = user();
        $companyId = company()->id;

        if (!$loggedUser->ability('admin', 'leaves_view')) {
            throw new ApiException("Not have valid permission");
        }

        $leaveType = \App\Models\LeaveType::where('company_id', $companyId)
            ->get()
            ->first(fn($t) => $t->isMonthlyLeave());

        if (!$leaveType) {
            throw new ApiException("Monthly Leave type not found");
        }

        $employeeSettings = EmployeeSpecificLeaveCount::where('leave_type_id', $leaveType->id)
            ->with('user:id,name')
            ->get()
            ->map(fn($record) => [
                'xid' => $record->xid,
                'user_id' => \Vinkla\Hashids\Facades\Hashids::encode($record->user_id),
                'user_name' => $record->user->name ?? '',
                'monthly_leave_expiry_cycle' => $record->monthly_leave_expiry_cycle,
                'max_leaves_per_month' => $record->max_leaves_per_month ?? 0,
            ]);

        return ApiResponse::make(null, [
            'leave_type' => [
                'xid' => $leaveType->xid,
                'name' => $leaveType->name,
                'is_paid' => $leaveType->is_paid,
                'monthly_leave_expiry_cycle' => $leaveType->monthly_leave_expiry_cycle ?? 3,
                'max_leaves_per_month' => $leaveType->max_leaves_per_month ?? 0,
            ],
            'employee_settings' => $employeeSettings,
        ]);
    }

    /**
     * PUT /employee-monthly-leaves/settings
     */
    public function updateSettings(Request $request)
    {
        $loggedUser = user();
        $companyId = company()->id;

        if (!$loggedUser->ability('admin', 'leaves_edit')) {
            throw new ApiException("Not have valid permission");
        }

        $request->validate([
            'monthly_leave_expiry_cycle' => 'required|integer|min:1',
            'max_leaves_per_month' => 'nullable|integer|min:0',
            'employee_settings' => 'nullable|array',
            'employee_settings.*.user_id' => 'required_with:employee_settings',
            'employee_settings.*.monthly_leave_expiry_cycle' => 'nullable|integer|min:1',
            'employee_settings.*.max_leaves_per_month' => 'nullable|integer|min:0',
        ]);

        $leaveType = \App\Models\LeaveType::where('company_id', $companyId)
            ->get()
            ->first(fn($t) => $t->isMonthlyLeave());

        if (!$leaveType) {
            throw new ApiException("Monthly Leave type not found");
        }

        $leaveType->update([
            'monthly_leave_expiry_cycle' => $request->monthly_leave_expiry_cycle,
            'max_leaves_per_month' => $request->max_leaves_per_month ?? 0,
        ]);

        if ($request->has('employee_settings')) {
            foreach ($request->employee_settings as $setting) {
                $employeeId = $this->getIdFromHash($setting['user_id']);
                $cycle = $setting['monthly_leave_expiry_cycle'] ?? null;
                $maxPerMonth = $setting['max_leaves_per_month'] ?? 0;

                EmployeeSpecificLeaveCount::updateOrCreate(
                    [
                        'user_id' => $employeeId,
                        'leave_type_id' => $leaveType->id,
                    ],
                    [
                        'monthly_leave_expiry_cycle' => $cycle,
                        'max_leaves_per_month' => $maxPerMonth,
                        'total_leaves' => 0,
                    ]
                );
            }
        }

        return ApiResponse::make("Settings updated successfully", []);
    }

    /**
     * POST /employee-monthly-leaves/credit
     */
    public function credit(Request $request)
    {
        $loggedUser = user();
        $companyId = company()->id;

        if (!$loggedUser->ability('admin', 'leaves_edit')) {
            throw new ApiException("Not have valid permission");
        }

        $request->validate([
            'user_id' => 'required',
            'credited_date' => 'nullable|date',
        ]);

        $employeeId = $this->getIdFromHash($request->user_id);

        $leaveType = \App\Models\LeaveType::where('company_id', $companyId)
            ->get()
            ->first(fn($t) => $t->isMonthlyLeave());

        if (!$leaveType) {
            throw new ApiException("Monthly Leave type not found");
        }

        $creditedDate = $request->credited_date
            ? Carbon::parse($request->credited_date)->startOfMonth()->toDateString()
            : Carbon::now()->startOfMonth()->toDateString();

        $existing = EmployeeMonthlyLeave::where('employee_id', $employeeId)
            ->where('company_id', $companyId)
            ->whereDate('credited_date', $creditedDate)
            ->exists();

        if ($existing) {
            throw new ApiException("A monthly leave for this employee already exists for this month.");
        }

        EmployeeMonthlyLeave::create([
            'employee_id' => $employeeId,
            'company_id' => $companyId,
            'leave_type_id' => $leaveType->id,
            'credited_date' => $creditedDate,
            'status' => 'ACTIVE',
        ]);

        return ApiResponse::make("Monthly leave credited successfully", []);
    }
}