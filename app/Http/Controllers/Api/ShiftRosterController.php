<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\ShiftRoster;
use App\Models\StaffMember;
use App\Models\Shift;
use App\Classes\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ShiftRosterController extends ApiBaseController
{
    protected $model = ShiftRoster::class;

    public function weekly(Request $request)
    {
        $company = company();
        $requestUserId = $request->user_id;
        $startDate = $request->start_date ? Carbon::parse($request->start_date) : Carbon::now()->startOfWeek(Carbon::MONDAY);
        $endDate = $request->end_date ? Carbon::parse($request->end_date) : $startDate->copy()->endOfWeek(Carbon::SUNDAY);

        $users = StaffMember::select('id', 'name', 'shift_id')
            ->where('company_id', $company->id)
            ->get();

        $rosters = ShiftRoster::with(['shift'])
            ->where('company_id', $company->id)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->groupBy('user_id');

        $dates = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates[] = $date->format('Y-m-d');
        }

        $result = [];
        foreach ($users as $user) {
            $userXid = $user->xid;

            if ($requestUserId && $requestUserId !== $userXid) {
                continue;
            }

            $dayRosters = [];
            foreach ($dates as $date) {
                $rosterForDate = isset($rosters[$user->id])
                    ? $rosters[$user->id]->firstWhere('date', $date)
                    : null;

                $dayRosters[] = [
                    'date' => $date,
                    'shift' => $rosterForDate && $rosterForDate->shift ? [
                        'xid' => $rosterForDate->shift->xid,
                        'name' => $rosterForDate->shift->name,
                        'clock_in_time' => $rosterForDate->shift->clock_in_time,
                        'clock_out_time' => $rosterForDate->shift->clock_out_time,
                    ] : null,
                    'roster_xid' => $rosterForDate ? $rosterForDate->xid : null,
                ];
            }

            $result[] = [
                'user' => [
                    'xid' => $userXid,
                    'name' => $user->name,
                ],
                'rosters' => $dayRosters,
            ];
        }

        return [
            'data' => $result,
            'dates' => $dates,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ];
    }

    public function assign(Request $request)
    {
        $company = company();
        $userId = Common::getIdFromHash($request->user_id);
        $shiftId = Common::getIdFromHash($request->shift_id);
        $date = $request->date;

        $roster = ShiftRoster::where('company_id', $company->id)
            ->where('user_id', $userId)
            ->where('date', $date)
            ->first();

        if ($roster) {
            $roster->shift_id = $shiftId;
            $roster->save();
        } else {
            $roster = new ShiftRoster();
            $roster->company_id = $company->id;
            $roster->user_id = $userId;
            $roster->shift_id = $shiftId;
            $roster->date = $date;
            $roster->save();
        }

        return apiResponse($roster);
    }

    public function remove(Request $request)
    {
        $company = company();
        $userId = Common::getIdFromHash($request->user_id);
        $date = $request->date;

        ShiftRoster::where('company_id', $company->id)
            ->where('user_id', $userId)
            ->where('date', $date)
            ->delete();

        return apiResponse(null);
    }
}
