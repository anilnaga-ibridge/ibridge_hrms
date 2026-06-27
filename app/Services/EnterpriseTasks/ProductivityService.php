<?php

namespace App\Services\EnterpriseTasks;

use App\Repositories\EnterpriseTasks\ProductivityRepository;
use App\Models\EnterpriseTasks\UserProductivityScore;
use App\Models\EnterpriseTasks\Task;
use App\Models\EnterpriseTasks\TaskUser;
use App\Models\EnterpriseTasks\TaskComment;
use App\Models\EnterpriseTasks\CommentReaction;
use App\Models\EnterpriseTasks\TaskActivity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductivityService
{
    protected ProductivityRepository $repository;

    public function __construct(ProductivityRepository $repository)
    {
        $this->repository = $repository;
    }

    public function calculateUserScore(int $companyId, int $userId, int $year, int $month): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // 1. Task Completion (40%)
        // Fetch tasks assigned to the user that were due or completed in this month
        $assignedTaskIds = TaskUser::where('user_id', $userId)
            ->where('type', 'assignee')
            ->pluck('task_id');

        $tasks = Task::whereIn('id', $assignedTaskIds)
            ->where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where(function($q) use ($startDate, $endDate) {
                $q->whereBetween('due_date', [$startDate, $endDate])
                  ->orWhereBetween('completion_date', [$startDate, $endDate]);
            })
            ->get();

        $totalTasks = $tasks->count();
        if ($totalTasks === 0) {
            // Base score if no tasks
            return [
                'completion_pct' => 100.0,
                'on_time_pct' => 100.0,
                'time_accuracy' => 100.0,
                'collaboration' => 100.0,
                'reopen_penalty' => 0.0,
                'final_score' => 100.0,
                'breakdown' => [
                    'completion_score' => 40.0,
                    'on_time_score' => 20.0,
                    'time_accuracy_score' => 20.0,
                    'collaboration_score' => 10.0,
                    'reopen_penalty_score' => 10.0
                ]
            ];
        }

        $completedTasks = $tasks->where('status', 'completed');
        $completedCount = $completedTasks->count();
        $completionPct = ($completedCount / $totalTasks) * 100;
        $completionWeighted = $completionPct * 0.40;

        // 2. On-Time Delivery (20%)
        $onTimeCount = 0;
        foreach ($completedTasks as $t) {
            if (!$t->due_date || !$t->completion_date) {
                $onTimeCount++;
            } else {
                $due = Carbon::parse($t->due_date);
                $comp = Carbon::parse($t->completion_date);
                if ($comp->lte($due)) {
                    $onTimeCount++;
                }
            }
        }
        $onTimePct = $completedCount > 0 ? ($onTimeCount / $completedCount) * 100 : 0.0;
        $onTimeWeighted = $onTimePct * 0.20;

        // 3. Time Tracking Accuracy (20%)
        $estimatedHours = 0.0;
        $actualHours = 0.0;
        foreach ($tasks as $t) {
            $estimatedHours += (float)($t->estimated_hours ?? 0);
            $actualHours += (float)($t->actual_hours ?? 0);
        }

        $timeAccuracy = 100.0;
        if ($estimatedHours > 0.0) {
            $variance = abs($estimatedHours - $actualHours) / $estimatedHours;
            $variance = min(1.0, $variance); // Cap variance at 100%
            $timeAccuracy = (1.0 - $variance) * 100;
        } else if ($actualHours > 0.0) {
            $timeAccuracy = 0.0; // Tracked time with 0 estimate
        }
        $timeWeighted = $timeAccuracy * 0.20;

        // 4. Collaboration Score (10%)
        // MIN(100, (Comments * 10) + (Reactions * 5) + (Reviewer/Watcher tasks * 15))
        $commentsCount = TaskComment::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $reactionsCount = CommentReaction::where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $reviewerWatcherCount = TaskUser::where('user_id', $userId)
            ->whereIn('type', ['reviewer', 'watcher'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $collabRaw = ($commentsCount * 10) + ($reactionsCount * 5) + ($reviewerWatcherCount * 15);
        $collaborationScore = min(100.0, (float)$collabRaw);
        $collabWeighted = $collaborationScore * 0.10;

        // 5. Reopened Task Penalty (10%)
        // We look for activities where user's task was reopened
        $taskIds = $tasks->pluck('id')->toArray();
        $reopenedCount = 0;
        if (!empty($taskIds)) {
            $reopenedCount = TaskActivity::whereIn('task_id', $taskIds)
                ->where('activity_type', 'status_change')
                ->where(function($q) {
                    $q->where('description', 'like', '%completed to%')
                      ->orWhere('description', 'like', '%status changed from completed to%');
                })
                ->count();
        }

        $reopenPct = $completedCount > 0 ? min(100.0, ($reopenedCount / max(1, $completedCount)) * 100) : 0.0;
        $reopenScore = 100.0 - $reopenPct;
        $reopenWeighted = $reopenScore * 0.10;

        $finalScore = $completionWeighted + $onTimeWeighted + $timeWeighted + $collabWeighted + $reopenWeighted;

        return [
            'completion_pct' => round($completionPct, 2),
            'on_time_pct' => round($onTimePct, 2),
            'time_accuracy' => round($timeAccuracy, 2),
            'collaboration' => round($collaborationScore, 2),
            'reopen_penalty' => round($reopenPct, 2),
            'final_score' => round($finalScore, 2),
            'breakdown' => [
                'completion_score' => round($completionWeighted, 2),
                'on_time_score' => round($onTimeWeighted, 2),
                'time_accuracy_score' => round($timeWeighted, 2),
                'collaboration_score' => round($collabWeighted, 2),
                'reopen_penalty_score' => round($reopenWeighted, 2)
            ]
        ];
    }

    public function recalculateCompanyProductivity(int $companyId, int $year, int $month): void
    {
        $users = User::where('company_id', $companyId)->where('status', 'active')->get();
        $scores = [];

        foreach ($users as $user) {
            $res = $this->calculateUserScore($companyId, $user->id, $year, $month);
            $scores[] = [
                'user_id' => $user->id,
                'res' => $res
            ];
        }

        // Sort by final score desc
        usort($scores, function($a, $b) {
            return $b['res']['final_score'] <=> $a['res']['final_score'];
        });

        DB::transaction(function() use ($companyId, $year, $month, $scores) {
            foreach ($scores as $index => $item) {
                $rank = $index + 1;
                $res = $item['res'];

                UserProductivityScore::updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'user_id' => $item['user_id'],
                        'year' => $year,
                        'month' => $month
                    ],
                    [
                        'completion_percentage' => $res['completion_pct'],
                        'on_time_percentage' => $res['on_time_pct'],
                        'time_log_accuracy' => $res['time_accuracy'],
                        'collaboration_score' => $res['collaboration'],
                        'reopened_penalty' => $res['reopen_penalty'],
                        'final_score' => $res['final_score'],
                        'rank' => $rank,
                        'metrics_json' => $res['breakdown']
                    ]
                );

                // Invalidate Cache productivity:month:{userId}
                Cache::forget("productivity:month:{$item['user_id']}");
            }
        });
    }

    public function getUserScoreCached(int $companyId, int $userId, int $year, int $month): array
    {
        return Cache::remember("productivity:month:{$userId}", 3600, function() use ($companyId, $userId, $year, $month) {
            // Check if snapshot exists in db
            $snapshot = UserProductivityScore::where('company_id', $companyId)
                ->where('user_id', $userId)
                ->where('year', $year)
                ->where('month', $month)
                ->first();

            if ($snapshot) {
                return [
                    'completion_pct' => $snapshot->completion_percentage,
                    'on_time_pct' => $snapshot->on_time_percentage,
                    'time_accuracy' => $snapshot->time_log_accuracy,
                    'collaboration' => $snapshot->collaboration_score,
                    'reopen_penalty' => $snapshot->reopened_penalty,
                    'final_score' => $snapshot->final_score,
                    'rank' => $snapshot->rank,
                    'breakdown' => $snapshot->metrics_json
                ];
            }

            // Otherwise, calculate live
            $res = $this->calculateUserScore($companyId, $userId, $year, $month);
            $res['rank'] = null;
            return $res;
        });
    }
}
