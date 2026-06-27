<?php

namespace App\Jobs\EnterpriseTasks;

use App\Models\EnterpriseTasks\CalendarIntegration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CalendarSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job to perform mock/background synchronization of external calendars.
     */
    public function handle(): void
    {
        Log::info("Starting background calendar synchronization...");

        // Fetch all active calendar integrations
        $integrations = CalendarIntegration::where('sync_enabled', true)->get();

        foreach ($integrations as $integration) {
            try {
                // In a production environment, this would hit Google Calendar/Outlook APIs
                // with the stored access/refresh tokens to pull/push updates.
                $integration->update([
                    'last_synced_at' => now()
                ]);

                Log::info("Synchronized calendar integration [{$integration->id}] for user [{$integration->user_id}]");
            } catch (\Exception $e) {
                Log::error("Failed to sync calendar integration [{$integration->id}]: " . $e->getMessage());
            }
        }

        Log::info("Finished background calendar synchronization.");
    }
}
