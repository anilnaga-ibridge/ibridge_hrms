<?php

namespace App\Services\EnterpriseTasks;

use App\Models\User;
use App\Models\EnterpriseTasks\NotificationPreference;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Send push notification to a user.
     *
     * @param int $companyId
     * @param int $userId
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    public function sendPush(int $companyId, int $userId, string $title, string $body, array $data = []): void
    {
        $preference = NotificationPreference::where('company_id', $companyId)
            ->where('user_id', $userId)
            ->first();

        // Check if push is enabled (default true if not configured)
        if ($preference && !$preference->push) {
            Log::info("Push notification skipped for user [{$userId}] due to preferences.");
            return;
        }

        // Log push details (or integrate with Pusher/WebSockets/WebPush if needed)
        Log::info("Sending push notification to user [{$userId}]: {$title} - {$body}", [
            'data' => $data,
            'company_id' => $companyId
        ]);

        // Integrate with event broadcasting (WebSocket)
        try {
            event(new \App\Events\EnterpriseTasks\NotificationCreated($userId, [
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'created_at' => now()->toIso8601String()
            ]));
        } catch (\Exception $e) {
            Log::error("Failed to broadcast push event: " . $e->getMessage());
        }
    }
}
