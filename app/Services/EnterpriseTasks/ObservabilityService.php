<?php

namespace App\Services\EnterpriseTasks;

use App\Models\EnterpriseTasks\ObservabilityLog;
use Throwable;

class ObservabilityService
{
    const TYPE_API_ERROR            = 'api_error';
    const TYPE_QUEUE_FAILURE        = 'queue_failure';
    const TYPE_SCHEDULER_FAILURE    = 'scheduler_failure';
    const TYPE_NOTIFICATION_FAILURE = 'notification_failure';
    const TYPE_SLOW_QUERY           = 'slow_query';
    const TYPE_AI_ERROR             = 'ai_error';
    const TYPE_SYNC_ERROR           = 'sync_error';

    const SEV_DEBUG    = 'debug';
    const SEV_INFO     = 'info';
    const SEV_WARNING  = 'warning';
    const SEV_ERROR    = 'error';
    const SEV_CRITICAL = 'critical';

    /**
     * Log an event to ep_observability_logs.
     */
    public static function log(
        string $type,
        string $message,
        array $context = [],
        string $severity = self::SEV_ERROR,
        ?int $companyId = null,
        ?string $channel = null,
        ?int $durationMs = null
    ): void {
        try {
            ObservabilityLog::create([
                'company_id'  => $companyId,
                'type'        => $type,
                'channel'     => $channel,
                'message'     => $message,
                'context'     => $context,
                'severity'    => $severity,
                'duration_ms' => $durationMs,
            ]);
        } catch (Throwable $e) {
            // Never let observability crash the app
            logger()->error('ObservabilityService::log failed: ' . $e->getMessage());
        }
    }

    /**
     * Log an exception.
     */
    public static function exception(
        Throwable $e,
        string $type = self::TYPE_API_ERROR,
        array $context = [],
        ?int $companyId = null,
        ?string $channel = null
    ): void {
        static::log(
            $type,
            $e->getMessage(),
            array_merge($context, [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 10),
            ]),
            self::SEV_ERROR,
            $companyId,
            $channel
        );
    }

    /**
     * Log a slow query.
     */
    public static function slowQuery(string $sql, int $durationMs, ?int $companyId = null): void
    {
        static::log(
            self::TYPE_SLOW_QUERY,
            "Slow query detected ({$durationMs}ms)",
            ['sql' => $sql],
            self::SEV_WARNING,
            $companyId,
            'database',
            $durationMs
        );
    }

    /**
     * Get recent logs for the admin dashboard.
     */
    public static function recentLogs(?int $companyId = null, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return ObservabilityLog::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get error counts grouped by type for dashboard charts.
     */
    public static function errorSummary(?int $companyId = null): array
    {
        return ObservabilityLog::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->selectRaw('type, severity, COUNT(*) as count')
            ->groupBy('type', 'severity')
            ->get()
            ->toArray();
    }
}
