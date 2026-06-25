<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\RotationalTeam;
use App\Models\RotationalTeamMember;
use App\Models\RotationalSchedule;
use App\Models\StaffMember;
use App\Classes\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Examyou\RestAPI\Exceptions\ApiException;

use App\Http\Requests\Api\RotationalTeam\IndexRequest;
use App\Http\Requests\Api\RotationalTeam\StoreRequest;
use App\Http\Requests\Api\RotationalTeam\UpdateRequest;
use App\Http\Requests\Api\RotationalTeam\DeleteRequest;

class RotationalTeamController extends ApiBaseController
{
    protected $model = RotationalTeam::class;

    protected $indexRequest = IndexRequest::class;
    protected $storeRequest = StoreRequest::class;
    protected $updateRequest = UpdateRequest::class;
    protected $deleteRequest = DeleteRequest::class;

    protected function modifyIndex($query)
    {
        return $query->withCount('members');
    }

    public function members(Request $request)
    {
        $company = company();
        $teamId = Common::getIdFromHash($request->team_id);

        $team = RotationalTeam::where('company_id', $company->id)
            ->where('id', $teamId)
            ->firstOrFail();

        $memberIds = RotationalTeamMember::where('rotational_team_id', $team->id)
            ->pluck('user_id');

        $users = StaffMember::select('id', 'name', 'employee_number', 'designation_id')
            ->with('designation')
            ->where('company_id', $company->id)
            ->whereIn('id', $memberIds)
            ->get()
            ->map(function ($user) {
                return [
                    'xid' => $user->xid,
                    'name' => $user->name,
                    'employee_number' => $user->employee_number,
                    'designation' => $user->designation ? $user->designation->name : '',
                ];
            });

        return apiResponse($users);
    }

    public function assignMembers(Request $request)
    {
        $company = company();
        $teamId = Common::getIdFromHash($request->team_id);
        $userXids = $request->user_ids ?? [];

        $team = RotationalTeam::where('company_id', $company->id)
            ->where('id', $teamId)
            ->firstOrFail();

        $userIds = [];
        foreach ($userXids as $xid) {
            $userIds[] = Common::getIdFromHash($xid);
        }

        // Validate all member IDs belong to the current company before assignment
        if (!empty($userIds)) {
            $validCount = StaffMember::where('company_id', $company->id)
                ->whereIn('id', $userIds)
                ->count();
            if ($validCount !== count($userIds)) {
                throw new ApiException('One or more selected members are invalid or belong to a different company.');
            }
        }

        $existingMemberIds = RotationalTeamMember::where('rotational_team_id', $team->id)
            ->pluck('user_id')
            ->toArray();

        $newIds = array_diff($userIds, $existingMemberIds);

        foreach ($newIds as $userId) {
            $existing = RotationalTeamMember::where('company_id', $company->id)
                ->where('user_id', $userId)
                ->first();

            if ($existing) {
                $existing->rotational_team_id = $team->id;
                $existing->save();
            } else {
                RotationalTeamMember::create([
                    'company_id' => $company->id,
                    'rotational_team_id' => $team->id,
                    'user_id' => $userId,
                ]);
            }
        }

        $toRemove = array_diff($existingMemberIds, $userIds);
        if (!empty($toRemove)) {
            RotationalTeamMember::where('rotational_team_id', $team->id)
                ->whereIn('user_id', $toRemove)
                ->delete();
        }

        return apiResponse(null);
    }

    public function transferMember(Request $request)
    {
        $company = company();
        $employeeId = Common::getIdFromHash($request->employee_id);
        $toTeamId = Common::getIdFromHash($request->to_team_id);

        $membership = RotationalTeamMember::where('company_id', $company->id)
            ->where('user_id', $employeeId)
            ->first();

        if (!$membership) {
            return apiResponse(null, 'Employee is not assigned to any team');
        }

        $toTeam = RotationalTeam::where('company_id', $company->id)
            ->where('id', $toTeamId)
            ->firstOrFail();

        $membership->rotational_team_id = $toTeam->id;
        $membership->save();

        return apiResponse(null);
    }

    // Schedule: stores only the team that is OFF on each Saturday.
    // One row per Saturday. is_weekoff = true means this team is off.
    public function schedule(Request $request)
    {
        $company = company();
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : $startDate->copy()->endOfMonth();

        $teams = RotationalTeam::withCount('members')
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('rotation_order')
            ->get();

        $offSchedules = RotationalSchedule::where('company_id', $company->id)
            ->where('is_weekoff', true)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->keyBy('date');

        $dates = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->dayOfWeek === Carbon::SATURDAY) {
                $dates[] = $date->format('Y-m-d');
            }
        }

        $result = [];
        foreach ($dates as $date) {
            $offEntry = isset($offSchedules[$date]) ? $offSchedules[$date] : null;
            $offTeam = null;
            if ($offEntry) {
                $team = $teams->firstWhere('id', $offEntry->rotational_team_id);
                if ($team) {
                    $offTeam = [
                        'xid' => $team->xid,
                        'name' => $team->name,
                    ];
                }
            }

            $result[] = [
                'date' => $date,
                'off_team' => $offTeam,
                'schedule_xid' => $offEntry ? $offEntry->xid : null,
            ];
        }

        return [
            'data' => $result,
            'teams' => $teams->map(function ($t) {
                return [
                    'xid' => $t->xid,
                    'name' => $t->name,
                    'rotation_order' => $t->rotation_order,
                    'members_count' => $t->members_count,
                    'is_active' => (bool) $t->is_active,
                ];
            }),
            'dates' => $dates,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }

    public function updateSchedule(Request $request)
    {
        $company = company();
        $date = $request->date;
        
        $offTeamId = null;
        if ($request->has('off_team_id') && $request->off_team_id != '') {
            $offTeamId = Common::getIdFromHash($request->off_team_id);
            $teamExists = RotationalTeam::where('company_id', $company->id)
                ->where('id', $offTeamId)
                ->exists();
            if (!$offTeamId || !$teamExists) {
                throw new ApiException('Selected off-team is invalid or does not belong to your company.');
            }
        }

        // Remove existing off-team entry for this date
        RotationalSchedule::where('company_id', $company->id)
            ->where('date', $date)
            ->where('is_weekoff', true)
            ->delete();

        if ($offTeamId) {
            RotationalSchedule::create([
                'company_id' => $company->id,
                'rotational_team_id' => $offTeamId,
                'date' => $date,
                'is_weekoff' => true,
            ]);
        }

        return apiResponse(null);
    }

    public function autoGenerate(Request $request)
    {
        $company = company();
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : $startDate->copy()->endOfMonth()->addMonth();

        $activeTeams = RotationalTeam::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('rotation_order')
            ->get();

        if ($activeTeams->isEmpty()) {
            return apiResponse(null, 'No active teams found');
        }

        $teamCount = $activeTeams->count();
        $teamIds = $activeTeams->pluck('id')->toArray();

        $saturdays = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if ($date->dayOfWeek === Carbon::SATURDAY) {
                $saturdays[] = $date->format('Y-m-d');
            }
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($company, $startDate, $endDate, $activeTeams, $teamCount, $saturdays) {
            // Remove existing off-schedule in range
            RotationalSchedule::where('company_id', $company->id)
                ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                ->where('is_weekoff', true)
                ->delete();

            $teamIndex = 0;
            foreach ($saturdays as $satDate) {
                $offTeam = $activeTeams[$teamIndex];

                RotationalSchedule::create([
                    'company_id' => $company->id,
                    'rotational_team_id' => $offTeam->id,
                    'date' => $satDate,
                    'is_weekoff' => true,
                ]);

                $teamIndex = ($teamIndex + 1) % $teamCount;
            }
        });

        return apiResponse(null);
    }

    public function unassignedEmployees(Request $request)
    {
        $company = company();

        $assignedUserIds = RotationalTeamMember::where('company_id', $company->id)
            ->pluck('user_id');

        $totalEmployees = StaffMember::where('company_id', $company->id)->count();
        $assignedCount = $assignedUserIds->count();
        $unassignedCount = $totalEmployees - $assignedCount;

        $users = StaffMember::select('id', 'name', 'employee_number', 'designation_id')
            ->with('designation')
            ->where('company_id', $company->id)
            ->whereNotIn('id', $assignedUserIds)
            ->get()
            ->map(function ($user) {
                return [
                    'xid' => $user->xid,
                    'name' => $user->name,
                    'employee_number' => $user->employee_number,
                    'designation' => $user->designation ? $user->designation->name : '',
                ];
            });

        return [
            'data' => [
                'total_employees' => $totalEmployees,
                'assigned_count' => $assignedCount,
                'unassigned_count' => $unassignedCount,
                'unassigned_employees' => $users,
            ],
        ];
    }

    public function availableEmployees(Request $request)
    {
        $company = company();

        $assignedUserIds = RotationalTeamMember::where('company_id', $company->id)
            ->pluck('user_id');

        $users = StaffMember::select('id', 'name', 'employee_number', 'designation_id')
            ->with('designation')
            ->where('company_id', $company->id)
            ->whereNotIn('id', $assignedUserIds)
            ->get()
            ->map(function ($user) {
                return [
                    'xid' => $user->xid,
                    'name' => $user->name,
                    'employee_number' => $user->employee_number,
                    'designation' => $user->designation ? $user->designation->name : '',
                ];
            });

        return apiResponse($users);
    }

    public function allEmployees(Request $request)
    {
        $company = company();

        $users = StaffMember::select('id', 'name', 'employee_number', 'designation_id')
            ->with('designation')
            ->where('company_id', $company->id)
            ->get()
            ->map(function ($user) use ($company) {
                $team = RotationalTeamMember::with('team')
                    ->where('company_id', $company->id)
                    ->where('user_id', $user->id)
                    ->first();

                return [
                    'xid' => $user->xid,
                    'name' => $user->name,
                    'employee_number' => $user->employee_number,
                    'designation' => $user->designation ? $user->designation->name : '',
                    'team_name' => $team && $team->team ? $team->team->name : null,
                    'team_xid' => $team && $team->team ? $team->team->xid : null,
                ];
            });

        return apiResponse($users);
    }

    public function distributeUnassigned(Request $request)
    {
        $company = company();

        $assignedUserIds = RotationalTeamMember::where('company_id', $company->id)
            ->pluck('user_id');

        $unassignedUsers = StaffMember::where('company_id', $company->id)
            ->whereNotIn('id', $assignedUserIds)
            ->get();

        if ($unassignedUsers->isEmpty()) {
            return apiResponse(null, 'No unassigned employees found');
        }

        $activeTeams = RotationalTeam::withCount('members')
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->get();

        if ($activeTeams->isEmpty()) {
            return apiResponse(null, 'No active teams found. Please create a team first.');
        }

        $teamCounts = [];
        foreach ($activeTeams as $team) {
            $teamCounts[$team->id] = $team->members_count;
        }

        foreach ($unassignedUsers as $user) {
            asort($teamCounts);
            reset($teamCounts);
            $targetTeamId = key($teamCounts);

            RotationalTeamMember::create([
                'company_id' => $company->id,
                'rotational_team_id' => $targetTeamId,
                'user_id' => $user->id,
            ]);

            $teamCounts[$targetTeamId]++;
        }

        return apiResponse(null, 'Employees distributed successfully');
    }

    public function clearSchedule(Request $request)
    {
        $company = company();
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : $startDate->copy()->endOfMonth();

        RotationalSchedule::where('company_id', $company->id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->where('is_weekoff', true)
            ->delete();

        return apiResponse(null, 'Schedule cleared successfully');
    }
}
