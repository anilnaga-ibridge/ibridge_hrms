<?php

namespace App\Services\EnterpriseTasks;

use App\Models\EnterpriseTasks\Task;
use App\Models\EnterpriseTasks\TaskUser;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AITaskAssistantService
{
    /**
     * Generate subtasks/checklist items based on a task title.
     *
     * @param string $taskTitle
     * @return array
     */
    public function generateSubtasks(string $taskTitle): array
    {
        // Try external LLM if configured
        $llmResult = $this->tryLLM('Generate 3-5 subtask checklist item names for a task named "' . $taskTitle . '". Return as a JSON string array.');
        if ($llmResult && is_array($llmResult)) {
            return $llmResult;
        }

        // Rule-based fallback
        $subtasks = [];
        $titleLower = strtolower($taskTitle);

        if (str_contains($titleLower, 'setup') || str_contains($titleLower, 'configure')) {
            $subtasks = ['Gather requirements', 'Prepare environment', 'Configure settings', 'Verify connection', 'Document setup'];
        } elseif (str_contains($titleLower, 'bug') || str_contains($titleLower, 'fix')) {
            $subtasks = ['Reproduce issue', 'Analyze logs & find root cause', 'Apply code fix', 'Run local tests', 'Submit pull request'];
        } elseif (str_contains($titleLower, 'design') || str_contains($titleLower, 'ui') || str_contains($titleLower, 'ux')) {
            $subtasks = ['Review wireframes', 'Create mockup drafts', 'Gather feedback', 'Refine design specs', 'Export assets'];
        } elseif (str_contains($titleLower, 'test') || str_contains($titleLower, 'qa')) {
            $subtasks = ['Write test cases', 'Execute manual testing', 'Perform regression tests', 'Report bugs', 'Verify fixes'];
        } elseif (str_contains($titleLower, 'document') || str_contains($titleLower, 'write')) {
            $subtasks = ['Create outline', 'Draft content', 'Review spelling & grammar', 'Add code snippets/diagrams', 'Publish & share'];
        } elseif (str_contains($titleLower, 'meeting') || str_contains($titleLower, 'call')) {
            $subtasks = ['Prepare agenda', 'Send invitations', 'Take meeting notes', 'Identify action items', 'Follow up'];
        } else {
            // Generic subtasks
            $subtasks = ['Analyze requirements', 'Execute implementation', 'Self-review & QA', 'Final delivery'];
        }

        return $subtasks;
    }

    /**
     * Suggest priority based on task attributes (title/description).
     *
     * @param string $title
     * @param string|null $description
     * @return string
     */
    public function suggestPriority(string $title, ?string $description = ''): string
    {
        $text = strtolower($title . ' ' . ($description ?: ''));

        // Scoring rules
        $score = 0;
        if (str_contains($text, 'urgent') || str_contains($text, 'asap') || str_contains($text, 'blocker')) {
            $score += 10;
        }
        if (str_contains($text, 'bug') || str_contains($text, 'broken') || str_contains($text, 'crash')) {
            $score += 5;
        }
        if (str_contains($text, 'critical') || str_contains($text, 'fatal')) {
            $score += 8;
        }
        if (str_contains($text, 'feature') || str_contains($text, 'enhancement')) {
            $score += 2;
        }
        if (str_contains($text, 'low') || str_contains($text, 'minor') || str_contains($text, 'nice to have')) {
            $score -= 5;
        }

        if ($score >= 10) return 'P1'; // Critical/Urgent
        if ($score >= 5)  return 'P2'; // High
        if ($score >= 0)  return 'P3'; // Medium
        return 'P4'; // Low
    }

    /**
     * Suggest description based on task title.
     *
     * @param string $taskTitle
     * @return string
     */
    public function generateDescription(string $taskTitle): string
    {
        $titleLower = strtolower($taskTitle);

        if (str_contains($titleLower, 'bug') || str_contains($titleLower, 'fix')) {
            return "### Steps to Reproduce\n1. Go to...\n2. Click on...\n3. Observe the bug.\n\n### Expected Behavior\n...\n\n### Actual Behavior\n...\n\n### Environment Details\n- OS/Browser: \n- Account ID: ";
        }

        if (str_contains($titleLower, 'create') || str_contains($titleLower, 'build') || str_contains($titleLower, 'implement')) {
            return "### Overview\nImplement feature: **{$taskTitle}** to meet client requirements.\n\n### Technical Design\n- Front-end changes: \n- Database migrations: \n- API endpoints: \n\n### Acceptance Criteria\n- [ ] UI matches mockup designs.\n- [ ] Unit/Integration tests pass.\n- [ ] Code is linted & documented.";
        }

        return "### Objective\nDescribe what needs to be achieved in this task.\n\n### Requirements\n- Point 1\n- Point 2\n\n### Additional Context\nProvide any relevant links, designs, or files here.";
    }

    /**
     * Generate standup summary of a user for a specific date.
     *
     * @param int $companyId
     * @param int $userId
     * @param string $date
     * @return array
     */
    public function generateStandupSummary(int $companyId, int $userId, string $date): array
    {
        $carbonDate = Carbon::parse($date);

        // Done: Tasks completed today
        $done = Task::where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where('status', 'completed')
            ->whereDate('completed_at', $carbonDate)
            ->where(function ($q) use ($userId) {
                $q->whereHas('users', function ($u) use ($userId) {
                    $u->where('users.id', $userId);
                })->orWhere('created_by', $userId);
            })
            ->pluck('title')
            ->toArray();

        // In Progress: Tasks modified or time-logged today
        $inProgress = Task::where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where('status', 'in_progress')
            ->where(function ($q) use ($userId) {
                $q->whereHas('users', function ($u) use ($userId) {
                    $u->where('users.id', $userId);
                })->orWhere('created_by', $userId);
            })
            ->pluck('title')
            ->toArray();

        // Blocked / Overdue: Overdue tasks
        $blocked = Task::where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where('status', '!=', 'completed')
            ->whereNotNull('due_date')
            ->where('due_date', '<', $carbonDate)
            ->where(function ($q) use ($userId) {
                $q->whereHas('users', function ($u) use ($userId) {
                    $u->where('users.id', $userId);
                })->orWhere('created_by', $userId);
            })
            ->pluck('title')
            ->toArray();

        return [
            'done' => $done,
            'in_progress' => $inProgress,
            'blocked' => $blocked
        ];
    }

    /**
     * Detect overloaded users in a company.
     *
     * @param int $companyId
     * @return array
     */
    public function detectOverloadedUsers(int $companyId): array
    {
        $users = User::where('company_id', $companyId)->get();
        $overloaded = [];

        foreach ($users as $user) {
            // Count pending tasks assigned to the user
            $pendingCount = Task::where('company_id', $companyId)
                ->where('is_deleted', false)
                ->where('status', '!=', 'completed')
                ->whereHas('users', function($q) use ($user) {
                    $q->where('users.id', $user->id);
                })
                ->count();

            // Threshold: 10 tasks
            if ($pendingCount >= 10) {
                $overloaded[] = [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ],
                    'pending_tasks_count' => $pendingCount,
                    'message' => "{$user->name} has {$pendingCount} pending tasks. Suggest redistribution."
                ];
            }
        }

        return $overloaded;
    }

    /**
     * Predict delay risk based on project metrics and task due date.
     *
     * @param int $taskId
     * @return array
     */
    public function predictDelay(int $taskId): array
    {
        $task = Task::findOrFail($taskId);
        
        if (!$task->due_date || $task->status === 'completed') {
            return ['risk' => 'none', 'message' => 'Task is completed or has no due date.'];
        }

        $dueDate = Carbon::parse($task->due_date);
        $daysRemaining = Carbon::now()->diffInDays($dueDate, false);

        if ($daysRemaining < 0) {
            return ['risk' => 'high', 'message' => 'Task is already overdue.'];
        }

        // Count checklist items
        $totalItems = 0;
        $completedItems = 0;
        foreach ($task->checklists as $checklist) {
            $totalItems += $checklist->items->count();
            $completedItems += $checklist->items->where('is_checked', true)->count();
        }

        $completionRate = $totalItems > 0 ? ($completedItems / $totalItems) : 1;

        if ($daysRemaining <= 2 && $completionRate < 0.5) {
            return ['risk' => 'high', 'message' => 'Due in ' . round($daysRemaining) . ' days with ' . round($completionRate * 100) . '% checklist completion.'];
        }

        if ($daysRemaining <= 5 && $completionRate < 0.3) {
            return ['risk' => 'medium', 'message' => 'Due in ' . round($daysRemaining) . ' days with low checklist completion.'];
        }

        return ['risk' => 'low', 'message' => 'On track.'];
    }

    /**
     * Try calling external LLM if credentials are configured in .env.
     */
    private function tryLLM(string $prompt): ?array
    {
        $provider = env('AI_PROVIDER');
        $apiKey = env('AI_API_KEY');

        if (!$provider || !$apiKey) {
            return null;
        }

        try {
            if ($provider === 'openai') {
                $response = Http::withToken($apiKey)->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt]
                    ],
                    'temperature' => 0.5
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $text = $json['choices'][0]['message']['content'] ?? '';
                    $decoded = json_decode($text, true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("AI Service LLM request failed: " . $e->getMessage());
        }

        return null;
    }
}
