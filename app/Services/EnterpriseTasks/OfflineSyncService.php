<?php

namespace App\Services\EnterpriseTasks;

use App\Models\EnterpriseTasks\SyncQueue;
use App\Models\EnterpriseTasks\Task;
use App\Models\Project;
use App\Classes\Common;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfflineSyncService
{
    /**
     * Push offline queued items to the main database and resolve conflicts.
     *
     * @param int $companyId
     * @param int $userId
     * @param array $mutations
     * @return array
     */
    public function processPush(int $companyId, int $userId, array $mutations): array
    {
        $results = [];

        DB::transaction(function () use ($companyId, $userId, $mutations, &$results) {
            foreach ($mutations as $mut) {
                $action = $mut['action'] ?? '';
                $modelName = $mut['model'] ?? '';
                $clientTimestamp = $mut['timestamp'] ?? now()->toIso8601String();
                $payload = $mut['payload'] ?? [];
                $clientXid = $mut['xid'] ?? null;

                // Log into SyncQueue
                $syncLog = SyncQueue::create([
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'action' => $action,
                    'model_type' => $modelName,
                    'payload' => $payload,
                    'status' => 'pending'
                ]);

                try {
                    $resolved = $this->resolveAndApply($companyId, $userId, $action, $modelName, $clientXid, $payload, $clientTimestamp);
                    
                    $syncLog->update([
                        'status' => 'processed',
                        'processed_at' => now()
                    ]);

                    $results[] = [
                        'xid' => $clientXid,
                        'status' => 'success',
                        'resolved' => $resolved
                    ];
                } catch (\Exception $e) {
                    Log::error("Offline sync item failed to process: " . $e->getMessage(), [
                        'mutation' => $mut
                    ]);

                    $syncLog->update([
                        'status' => 'conflict',
                        'conflict_details' => $e->getMessage()
                    ]);

                    $results[] = [
                        'xid' => $clientXid,
                        'status' => 'conflict',
                        'message' => $e->getMessage()
                    ];
                }
            }
        });

        return $results;
    }

    /**
     * Apply the offline action or resolve conflicts if server has newer data.
     */
    private function resolveAndApply(int $companyId, int $userId, string $action, string $modelName, ?string $clientXid, array $payload, string $clientTimestamp): bool
    {
        if ($modelName === 'Task') {
            if ($action === 'create') {
                // If it already exists on server, skip (or update)
                $existing = Task::where('company_id', $companyId)
                    ->where('title', $payload['title'] ?? '')
                    ->where('created_by', $userId)
                    ->first();

                if ($existing) {
                    return false; // Skip duplicate creation
                }

                // Resolve project id
                $projectId = null;
                if (!empty($payload['x_project_id'])) {
                    $projectId = Common::getIdFromHash($payload['x_project_id']);
                }

                // Create task
                Task::create([
                    'company_id' => $companyId,
                    'project_id' => $projectId,
                    'title' => $payload['title'] ?? 'Untitled Offline Task',
                    'description' => $payload['description'] ?? null,
                    'status' => $payload['status'] ?? 'pending',
                    'priority' => $payload['priority'] ?? 'P3',
                    'due_date' => $payload['due_date'] ?? null,
                    'created_by' => $userId
                ]);

                return true;
            }

            if ($action === 'update' && $clientXid) {
                $taskId = Common::getIdFromHash($clientXid);
                $task = Task::where('company_id', $companyId)->find($taskId);

                if ($task) {
                    // Conflict detection: If server was modified after client mutation was generated, server wins!
                    $serverUpdatedAt = $task->updated_at;
                    $clientTime = \Carbon\Carbon::parse($clientTimestamp);

                    if ($serverUpdatedAt && $serverUpdatedAt->gt($clientTime)) {
                        throw new \Exception("Conflict: Task was updated on the server at " . $serverUpdatedAt->toIso8601String() . " which is newer than offline edit at " . $clientTimestamp);
                    }

                    // Apply update
                    $task->update(array_filter([
                        'title' => $payload['title'] ?? null,
                        'description' => $payload['description'] ?? null,
                        'status' => $payload['status'] ?? null,
                        'priority' => $payload['priority'] ?? null,
                        'due_date' => $payload['due_date'] ?? null,
                    ]));
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Pull all updates since a given timestamp.
     *
     * @param int $companyId
     * @param string|null $since
     * @return array
     */
    public function pullChanges(int $companyId, ?string $since = null): array
    {
        $query = Task::where('company_id', $companyId)->where('is_deleted', false);

        if ($since) {
            $sinceTime = \Carbon\Carbon::parse($since);
            $query->where('updated_at', '>', $sinceTime);
        }

        $tasks = $query->get();

        return [
            'tasks' => $tasks->map(function ($task) {
                return [
                    'xid' => $task->xid,
                    'title' => $task->title,
                    'description' => $task->description,
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'due_date' => $task->due_date,
                    'updated_at' => $task->updated_at->toIso8601String()
                ];
            })->toArray(),
            'server_time' => now()->toIso8601String()
        ];
    }
}
