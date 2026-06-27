<?php

namespace App\Services\EnterpriseTasks;

use App\Repositories\EnterpriseTasks\NotificationRepository;
use App\Models\EnterpriseTasks\Notification;
use App\Models\EnterpriseTasks\NotificationQueue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected NotificationRepository $repository;

    public function __construct(NotificationRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getNotifications(int $userId)
    {
        return Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(50);
    }

    public function notify(int $companyId, int $userId, string $type, array $data): void
    {
        // 1. Create In-App Notification
        Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'data' => $data
        ]);

        // 2. Queue Email/Push Notification in High-priority Queue table
        NotificationQueue::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'notification_type' => $type,
            'data' => $data,
            'status' => 'pending',
            'attempts' => 0,
            'scheduled_at' => Carbon::now()
        ]);
    }

    public function markAsRead(int $userId, array $ids): void
    {
        Notification::where('user_id', $userId)
            ->whereIn('id', $ids)
            ->update(['read_at' => Carbon::now()]);
    }

    public function markAllRead(int $userId): void
    {
        Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => Carbon::now()]);
    }

    public function processQueue(): void
    {
        $pending = NotificationQueue::where('status', 'pending')
            ->where(function($q) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '<=', Carbon::now());
            })
            ->take(50)
            ->get();

        foreach ($pending as $item) {
            $item->update([
                'attempts' => $item->attempts + 1,
                'last_attempt_at' => Carbon::now()
            ]);

            try {
                $user = User::find($item->user_id);
                if ($user && $user->email) {
                    $title = $item->data['task_title'] ?? 'Task Alert';
                    $msg = $this->buildNotificationText($item->notification_type, $item->data);

                    // Send email using Laravel standard mail
                    Mail::raw($msg, function ($message) use ($user, $title) {
                        $message->to($user->email)
                            ->subject("[HRM Enterprise Tasks] " . $title);
                    });

                    $item->update(['status' => 'sent']);
                } else {
                    $item->update(['status' => 'failed']);
                }
            } catch (\Exception $e) {
                Log::error("Failed sending queued notification ID [{$item->id}]: " . $e->getMessage());
                $item->update(['status' => $item->attempts >= 3 ? 'failed' : 'pending']);
            }
        }
    }

    private function buildNotificationText(string $type, array $data): string
    {
        $title = $data['task_title'] ?? 'task';
        switch ($type) {
            case 'task_created':
                return "A new task has been created: \"{$title}\". Check details in your workspace.";
            case 'task_assigned':
                return "You have been assigned to the task: \"{$title}\". Please review it.";
            case 'task_status_changed':
                return "The status of task \"{$title}\" has changed. Check updates in your dashboard.";
            case 'task_completed':
                return "Congratulations! The task \"{$title}\" has been marked as completed.";
            case 'task_overdue':
                return "WARNING: The task \"{$title}\" is overdue. Please complete it as soon as possible.";
            case 'reminder':
                return "This is a reminder for your task: \"{$title}\".";
            default:
                return "There is a new update on your task: \"{$title}\".";
        }
    }
}
