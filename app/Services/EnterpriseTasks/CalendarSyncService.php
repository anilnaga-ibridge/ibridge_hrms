<?php

namespace App\Services\EnterpriseTasks;

use App\Models\User;
use App\Models\EnterpriseTasks\Task;
use App\Models\EnterpriseTasks\CalendarIntegration;
use App\Services\EnterpriseTasks\ICSFeedService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class CalendarSyncService
{
    protected ICSFeedService $icsService;

    public function __construct(ICSFeedService $icsService)
    {
        $this->icsService = $icsService;
    }

    /**
     * Generate the ICS feed string for a specific user.
     *
     * @param string $token
     * @return string
     */
    public function generateUserICSFeed(string $token): string
    {
        // Find calendar integration by token
        $integration = CalendarIntegration::where('access_token', $token)
            ->where('sync_enabled', true)
            ->firstOrFail();

        // Get all active tasks for this user
        $tasks = Task::where('company_id', $integration->company_id)
            ->where('is_deleted', false)
            ->where(function ($query) use ($integration) {
                $query->whereHas('users', function ($q) use ($integration) {
                    $q->where('users.id', $integration->user_id);
                })->orWhere('created_by', $integration->user_id);
            })
            ->get();

        return $this->icsService->generate($tasks);
    }

    /**
     * Get or generate the personal iCal URL for a user.
     *
     * @param int $companyId
     * @param int $userId
     * @return string
     */
    public function getICalUrl(int $companyId, int $userId): string
    {
        $integration = CalendarIntegration::firstOrCreate(
            [
                'company_id' => $companyId,
                'user_id' => $userId,
                'provider' => 'ics'
            ],
            [
                'access_token' => Str::random(40),
                'sync_enabled' => true
            ]
        );

        // Generate absolute URL to route 'enterprise-tasks.calendar.ics'
        return url("/api/v1/enterprise-tasks/calendar/feed/{$integration->access_token}.ics");
    }

    /**
     * Mock parsing incoming ICS file (for future import / two-way sync).
     */
    public function parseICS(string $icsContent): array
    {
        $events = [];
        $lines = explode("\n", str_replace("\r", "", $icsContent));
        $currentEvent = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if ($line === 'BEGIN:VEVENT') {
                $currentEvent = [];
            } elseif ($line === 'END:VEVENT') {
                if ($currentEvent) {
                    $events[] = $currentEvent;
                }
                $currentEvent = null;
            } elseif ($currentEvent !== null) {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    list($key, $val) = $parts;
                    // Handle attributes like DTSTART;VALUE=DATE
                    $cleanKey = explode(';', $key)[0];
                    $currentEvent[$cleanKey] = $val;
                }
            }
        }

        return $events;
    }
}
