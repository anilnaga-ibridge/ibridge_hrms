<?php

namespace App\Services\EnterpriseTasks;

use App\Models\EnterpriseTasks\Task;
use App\Models\Project;
use App\Models\EnterpriseTasks\ProjectSection;
use App\Models\User;
use App\Classes\Common;
use Carbon\Carbon;

class SmartSchedulerService
{
    /**
     * Suggest the best due date based on user's workload.
     *
     * @param int $userId
     * @param int $estimatedHours
     * @return array
     */
    public function suggestBestDueDate(int $userId, int $estimatedHours = 2): array
    {
        $today = Carbon::today();
        
        // Loop through the next 7 days to find the one with the lowest task load
        $bestDate = $today;
        $minHours = 9999;
        $workloadByDay = [];

        for ($i = 0; $i < 7; $i++) {
            $day = $today->copy()->addDays($i);
            
            // Skip weekends (optional but good for business)
            if ($day->isWeekend()) {
                continue;
            }

            // Calculate estimated hours for tasks due on this day
            $tasksDue = Task::where('is_deleted', false)
                ->where('status', '!=', 'completed')
                ->whereDate('due_date', $day)
                ->whereHas('users', function ($q) use ($userId) {
                    $q->where('users.id', $userId);
                })
                ->get();

            $hours = 0;
            foreach ($tasksDue as $task) {
                // If estimated hours not set, assume 2 hours
                $hours += $task->estimated_hours ?: 2;
            }

            $workloadByDay[$day->toDateString()] = $hours;

            if ($hours < $minHours) {
                $minHours = $hours;
                $bestDate = $day;
            }
        }

        // If today has less than 6 hours, suggest today. Otherwise, recommend the best day.
        $suggestion = $bestDate->toDateString();
        $reason = "User workload on {$suggestion} is currently low ({$minHours} hours assigned).";

        if ($workloadByDay[$today->toDateString()] > 6) {
            $reason = "User already has " . $workloadByDay[$today->toDateString()] . " hours of work due today. We recommend {$suggestion} instead.";
        }

        return [
            'suggested_date' => $suggestion,
            'reason' => $reason,
            'workload_by_day' => $workloadByDay
        ];
    }

    /**
     * Recommend an assignee for a project based on workload and skills.
     *
     * @param int $projectId
     * @param array $skills
     * @return array
     */
    public function suggestAssignee(int $projectId, array $skills = []): array
    {
        $project = Project::findOrFail($projectId);
        $memberHashes = $project->members;

        if (empty($memberHashes) || !is_array($memberHashes)) {
            return ['suggested_user' => null, 'reason' => 'No members in this project.'];
        }

        $userIds = array_map(function ($hash) {
            return Common::getIdFromHash($hash);
        }, $memberHashes);

        $users = User::whereIn('id', $userIds)->get();

        if ($users->isEmpty()) {
            return ['suggested_user' => null, 'reason' => 'No members in this project.'];
        }

        $candidates = [];

        foreach ($users as $user) {
            // Count pending tasks in this project
            $pendingCount = Task::where('project_id', $projectId)
                ->where('is_deleted', false)
                ->where('status', '!=', 'completed')
                ->whereHas('users', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                })
                ->count();

            $candidates[] = [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'xid' => Common::getHashFromId($user->id)
                ],
                'pending_tasks' => $pendingCount
            ];
        }

        // Sort candidates by pending tasks ascending (least busy first)
        usort($candidates, function ($a, $b) {
            return $a['pending_tasks'] <=> $b['pending_tasks'];
        });

        $best = $candidates[0];

        return [
            'suggested_user' => $best['user'],
            'reason' => "{$best['user']['name']} is currently the least busy member with {$best['pending_tasks']} active tasks.",
            'all_candidates' => $candidates
        ];
    }

    /**
     * Suggest the best section in a project for a task title.
     *
     * @param int $projectId
     * @param string $taskTitle
     * @return array
     */
    public function suggestSection(int $projectId, string $taskTitle): array
    {
        $sections = ProjectSection::where('project_id', $projectId)->get();
        if ($sections->isEmpty()) {
            return ['suggested_section' => null, 'reason' => 'No sections configured in project.'];
        }

        $titleLower = strtolower($taskTitle);
        $bestSection = $sections->first();
        $reason = "Defaulting to the first project section.";

        foreach ($sections as $section) {
            $secLower = strtolower($section->name);

            if (str_contains($titleLower, 'bug') || str_contains($titleLower, 'fix') || str_contains($titleLower, 'issue')) {
                if (str_contains($secLower, 'bug') || str_contains($secLower, 'backlog') || str_contains($secLower, 'to do') || str_contains($secLower, 'todo')) {
                    $bestSection = $section;
                    $reason = "Keyword match for bug/issue tracker section.";
                    break;
                }
            }

            if (str_contains($titleLower, 'test') || str_contains($titleLower, 'verify') || str_contains($titleLower, 'qa')) {
                if (str_contains($secLower, 'test') || str_contains($secLower, 'qa') || str_contains($secLower, 'review')) {
                    $bestSection = $section;
                    $reason = "Keyword match for QA/testing section.";
                    break;
                }
            }

            if (str_contains($titleLower, 'deploy') || str_contains($titleLower, 'release')) {
                if (str_contains($secLower, 'done') || str_contains($secLower, 'completed') || str_contains($secLower, 'deploy')) {
                    $bestSection = $section;
                    $reason = "Keyword match for release/done section.";
                    break;
                }
            }
        }

        return [
            'suggested_section' => [
                'id' => $bestSection->id,
                'name' => $bestSection->name,
                'xid' => Common::getHashFromId($bestSection->id)
            ],
            'reason' => $reason
        ];
    }
}
