<?php

namespace App\Services\EnterpriseTasks;

use App\Repositories\EnterpriseTasks\TimeLogRepository;
use App\DTOs\EnterpriseTasks\TimeLogDTO;
use App\Models\EnterpriseTasks\TimeLog;
use App\Models\EnterpriseTasks\Task;
use Exception;
use Carbon\Carbon;

class TimeLogService
{
    protected TimeLogRepository $repository;

    public function __construct(TimeLogRepository $repository)
    {
        $this->repository = $repository;
    }

    public function startTimeLog(int $taskId, int $userId): TimeLog
    {
        // Verify if a timer is already running for this user
        $running = TimeLog::where('user_id', $userId)
            ->whereNull('end_time')
            ->first();

        if ($running) {
            throw new Exception("You already have an active timer running on task: " . Task::find($running->task_id)->title);
        }

        return TimeLog::create([
            'task_id' => $taskId,
            'user_id' => $userId,
            'start_time' => Carbon::now()
        ]);
    }

    public function stopTimeLog(int $taskId, int $userId, ?string $memo): TimeLog
    {
        $running = TimeLog::where('task_id', $taskId)
            ->where('user_id', $userId)
            ->whereNull('end_time')
            ->first();

        if (!$running) {
            throw new Exception("No active timer found for this task and user.");
        }

        $endTime = Carbon::now();
        $durationMinutes = Carbon::parse($running->start_time)->diffInMinutes($endTime);

        $running->update([
            'end_time' => $endTime,
            'duration_minutes' => max(1, $durationMinutes), // At least 1 minute
            'memo' => $memo
        ]);

        $this->recalculateActualHours($taskId);

        return $running;
    }

    public function storeTimeLog(int $taskId, int $userId, TimeLogDTO $dto): TimeLog
    {
        $startTime = Carbon::parse($dto->log_date . ' ' . date('H:i:s'));
        $endTime = (clone $startTime)->addMinutes($dto->duration_minutes);

        $log = TimeLog::create([
            'task_id' => $taskId,
            'user_id' => $userId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration_minutes' => $dto->duration_minutes,
            'memo' => $dto->memo
        ]);

        $this->recalculateActualHours($taskId);

        return $log;
    }

    public function recalculateActualHours(int $taskId): void
    {
        $task = Task::find($taskId);
        if (!$task) return;

        $totalMinutes = TimeLog::where('task_id', $taskId)->sum('duration_minutes');
        $actualHours = round($totalMinutes / 60, 2);

        $task->update(['actual_hours' => $actualHours]);
    }
}
