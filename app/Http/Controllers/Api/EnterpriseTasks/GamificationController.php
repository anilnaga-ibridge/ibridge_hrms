<?php

namespace App\Http\Controllers\Api\EnterpriseTasks;

use App\Http\Controllers\Controller;
use App\Models\EnterpriseTasks\Goal;
use App\Models\EnterpriseTasks\PomodoroSession;
use App\Models\EnterpriseTasks\UserStreak;
use App\Models\EnterpriseTasks\UserAchievement;
use App\Services\EnterpriseTasks\GamificationService;
use App\Classes\Common;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GamificationController extends Controller
{
    protected GamificationService $gamificationService;

    public function __construct(GamificationService $gamificationService)
    {
        $this->gamificationService = $gamificationService;
    }

    public function indexAchievements(Request $request)
    {
        $companyId = company()->id;
        $userId = auth('api')->id();

        $streak = UserStreak::firstOrCreate(
            ['company_id' => $companyId, 'user_id' => $userId],
            ['daily_streak' => 0, 'weekly_streak' => 0]
        );

        $achievements = UserAchievement::where('user_id', $userId)
            ->with(['badge'])
            ->get();

        return response()->json([
            'streak' => $streak,
            'achievements' => $achievements
        ]);
    }

    public function indexGoals(Request $request)
    {
        $companyId = company()->id;
        $userId = auth('api')->id();
        $goals = Goal::where('company_id', $companyId)->where('user_id', $userId)->get();
        return response()->json($goals);
    }

    public function storeGoal(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'goal_type'  => 'required|in:tasks_completed,bugs_closed,time_logged',
            'target'     => 'required|integer|min:1',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = company()->id;
        $userId = auth('api')->id();

        $goal = Goal::create([
            'company_id'       => $companyId,
            'user_id'          => $userId,
            'name'             => $request->name,
            'goal_type'        => $request->goal_type,
            'target'           => $request->target,
            'current_progress' => 0,
            'start_date'       => $request->start_date,
            'end_date'         => $request->end_date,
            'status'           => 'active',
        ]);

        return response()->json($goal, 201);
    }

    public function updateGoal(Request $request, $id)
    {
        $goalId = Common::getIdFromHash($id);
        $goal = Goal::findOrFail($goalId);
        $goal->update($request->only(['name', 'target', 'start_date', 'end_date', 'status']));
        return response()->json($goal);
    }

    public function destroyGoal($id)
    {
        $goalId = Common::getIdFromHash($id);
        Goal::findOrFail($goalId)->delete();
        return response()->json(['success' => true]);
    }

    public function startPomodoro(Request $request)
    {
        $userId = auth('api')->id();
        $taskId = null;
        if ($request->has('x_task_id') && $request->x_task_id) {
            $taskId = Common::getIdFromHash($request->x_task_id);
        }

        $session = $this->gamificationService->startPomodoroSession($userId, $taskId);

        return response()->json($session, 201);
    }

    public function completePomodoro($id)
    {
        $sessionId = Common::getIdFromHash($id);
        $userId = auth('api')->id();

        $session = $this->gamificationService->completePomodoroSession($sessionId, $userId);

        return response()->json($session);
    }

    public function pomodoroStats(Request $request)
    {
        $userId = auth('api')->id();
        $today = Carbon::today();

        $dailySessions = PomodoroSession::where('user_id', $userId)
            ->whereDate('start_at', $today)
            ->where('completed', true)
            ->count();

        $weeklySessions = PomodoroSession::where('user_id', $userId)
            ->whereBetween('start_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->where('completed', true)
            ->count();

        return response()->json([
            'daily_sessions'  => $dailySessions,
            'weekly_sessions' => $weeklySessions,
        ]);
    }
}
