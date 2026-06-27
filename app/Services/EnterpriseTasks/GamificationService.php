<?php

namespace App\Services\EnterpriseTasks;

use Carbon\Carbon;
use App\Models\EnterpriseTasks\Task;
use App\Models\EnterpriseTasks\UserStreak;
use App\Models\EnterpriseTasks\Badge;
use App\Models\EnterpriseTasks\UserAchievement;
use App\Models\EnterpriseTasks\Goal;
use App\Models\EnterpriseTasks\PomodoroSession;

class GamificationService
{
    /**
     * Get the current streak and achievements for a user.
     *
     * @param int $companyId
     * @param int $userId
     * @return array
     */
    public function getUserStreakData(int $companyId, int $userId): array
    {
        $streak = UserStreak::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->first();

        $achievements = UserAchievement::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->with('badge')
            ->orderByDesc('unlocked_at')
            ->get();

        $goals = Goal::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->get();

        $pomodoros = PomodoroSession::where('user_id', $userId)
            ->whereDate('start_at', Carbon::today())
            ->where('completed', true)
            ->count();

        // Total tasks completed all time
        $totalCompleted = Task::where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where('status', 'completed')
            ->whereHas('taskUsers', function($q) use ($userId) {
                $q->where('user_id', $userId)->where('type', 'assignee');
            })
            ->count();

        return [
            'streak'         => $streak,
            'achievements'   => $achievements,
            'goals'          => $goals,
            'pomodoros_today' => $pomodoros,
            'total_completed' => $totalCompleted,
        ];
    }

    /**
     * Update the user's daily streak when they complete a task.
     *
     * @param int $companyId
     * @param int $userId
     * @return UserStreak
     */
    public function updateStreak(int $companyId, int $userId): UserStreak
    {
        $today = Carbon::today();

        $streak = UserStreak::firstOrCreate(
            ['company_id' => $companyId, 'user_id' => $userId],
            ['daily_streak' => 0, 'weekly_streak' => 0]
        );

        $lastDate = $streak->last_completed_date ? Carbon::parse($streak->last_completed_date) : null;

        if ($lastDate === null || $lastDate->isBefore($today)) {
            if ($lastDate && $lastDate->diffInDays($today) === 1) {
                // Consecutive day — extend streak
                $streak->daily_streak += 1;
            } elseif ($lastDate && $lastDate->isSameDay($today)) {
                // Already logged today, no change needed
            } else {
                // Streak broken
                $streak->daily_streak = 1;
            }

            $streak->last_completed_date = $today->toDateString();
            $streak->save();

            // Check if the streak triggers any badge unlock
            $this->checkAndAwardBadges($companyId, $userId, $streak->daily_streak, 'streak_days');
        }

        return $streak;
    }

    /**
     * Check badges and award those not yet earned.
     *
     * @param int $companyId
     * @param int $userId
     * @param int $value
     * @param string $requirementType
     */
    public function checkAndAwardBadges(int $companyId, int $userId, int $value, string $requirementType): void
    {
        $eligibleBadges = Badge::where('requirement_type', $requirementType)
            ->where('requirement_value', '<=', $value)
            ->get();

        $existingBadgeIds = UserAchievement::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->pluck('badge_id')
            ->toArray();

        foreach ($eligibleBadges as $badge) {
            if (!in_array($badge->id, $existingBadgeIds)) {
                UserAchievement::create([
                    'company_id'  => $companyId,
                    'user_id'     => $userId,
                    'badge_id'    => $badge->id,
                    'unlocked_at' => Carbon::now()
                ]);
            }
        }
    }

    /**
     * Update Goal progress when a task is completed.
     *
     * @param int $companyId
     * @param int $userId
     */
    public function updateGoalProgress(int $companyId, int $userId): void
    {
        $activeGoals = Goal::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where('goal_type', 'tasks_completed')
            ->get();

        foreach ($activeGoals as $goal) {
            $goal->current_progress = Task::where('company_id', $companyId)
                ->where('status', 'completed')
                ->whereDate('completion_date', '>=', $goal->start_date)
                ->whereDate('completion_date', '<=', $goal->end_date)
                ->whereHas('taskUsers', function($q) use ($userId) {
                    $q->where('user_id', $userId)->where('type', 'assignee');
                })
                ->count();

            if ($goal->current_progress >= $goal->target) {
                $goal->status = 'completed';
            }

            $goal->save();
        }
    }

    /**
     * Create or update a Pomodoro session.
     *
     * @param int $userId
     * @param int|null $taskId
     * @return PomodoroSession
     */
    public function startPomodoroSession(int $userId, ?int $taskId = null): PomodoroSession
    {
        return PomodoroSession::create([
            'user_id'   => $userId,
            'task_id'   => $taskId,
            'start_at'  => Carbon::now(),
            'duration'  => 25,
            'completed' => false
        ]);
    }

    /**
     * Complete a Pomodoro session.
     *
     * @param int $sessionId
     * @param int $userId
     * @return PomodoroSession
     */
    public function completePomodoroSession(int $sessionId, int $userId): PomodoroSession
    {
        $session = PomodoroSession::where('id', $sessionId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $session->end_at = Carbon::now();
        $session->completed = true;
        $session->save();

        return $session;
    }
}
