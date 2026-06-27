<?php

namespace App\Repositories\EnterpriseTasks;

use App\Models\EnterpriseTasks\Notification;
use App\Models\EnterpriseTasks\NotificationQueue;

class NotificationRepository
{
    public function getNotificationsForUser(int $userId)
    {
        return Notification::where('user_id', $userId)->orderBy('created_at', 'desc')->get();
    }

    public function markAllRead(int $userId): void
    {
        Notification::where('user_id', $userId)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function queueNotification(int $companyId, int $userId, string $type, array $data, ?string $scheduledAt = null): NotificationQueue
    {
        return NotificationQueue::create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'notification_type' => $type,
            'data' => $data,
            'status' => 'pending',
            'scheduled_at' => $scheduledAt
        ]);
    }

    public function getPendingQueue()
    {
        return NotificationQueue::where('status', 'pending')
            ->where(function($q) {
                $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
            })
            ->get();
    }
}
