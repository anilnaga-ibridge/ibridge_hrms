<?php

namespace App\Repositories\EnterpriseTasks;

use App\Models\EnterpriseTasks\TimeLog;

class TimeLogRepository
{
    public function getById(int $id): ?TimeLog
    {
        return TimeLog::find($id);
    }

    public function getActiveTimer(int $userId): ?TimeLog
    {
        return TimeLog::where('user_id', $userId)->whereNull('end_time')->first();
    }

    public function create(array $data): TimeLog
    {
        return TimeLog::create($data);
    }

    public function update(TimeLog $log, array $data): bool
    {
        return $log->update($data);
    }

    public function getLogsForTask(int $taskId)
    {
        return TimeLog::where('task_id', $taskId)->with('user')->orderBy('start_time', 'desc')->get();
    }

    public function getCompanyLogs(int $companyId)
    {
        return TimeLog::whereHas('task', function($q) use ($companyId) {
            $q->where('company_id', $companyId);
        })->with(['task.project', 'user'])->orderBy('start_time', 'desc')->get();
    }
}
