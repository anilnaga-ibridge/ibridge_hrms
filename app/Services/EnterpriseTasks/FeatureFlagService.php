<?php

namespace App\Services\EnterpriseTasks;

use App\Models\EnterpriseTasks\FeatureFlag;
use Illuminate\Support\Facades\Cache;

class FeatureFlagService
{
    // Supported feature flags
    const FEATURE_AI_ASSISTANT      = 'ai_assistant';
    const FEATURE_GANTT_CHART       = 'gantt_chart';
    const FEATURE_OFFLINE_MODE      = 'offline_mode';
    const FEATURE_CALENDAR_SYNC     = 'calendar_sync';
    const FEATURE_PUSH_NOTIFICATIONS = 'push_notifications';
    const FEATURE_GAMIFICATION      = 'gamification';

    const ALL_FEATURES = [
        self::FEATURE_AI_ASSISTANT,
        self::FEATURE_GANTT_CHART,
        self::FEATURE_OFFLINE_MODE,
        self::FEATURE_CALENDAR_SYNC,
        self::FEATURE_PUSH_NOTIFICATIONS,
        self::FEATURE_GAMIFICATION,
    ];

    /**
     * Check if a feature is enabled for a company.
     *
     * @param string $feature
     * @param int|null $companyId
     * @return bool
     */
    public function isEnabled(string $feature, ?int $companyId = null): bool
    {
        $cacheKey = "ep_feature:{$feature}:{$companyId}";

        return Cache::remember($cacheKey, 300, function () use ($feature, $companyId) {
            // Check company-specific flag first
            if ($companyId) {
                $flag = FeatureFlag::where('company_id', $companyId)
                    ->where('feature', $feature)
                    ->first();

                if ($flag) {
                    return $flag->is_enabled;
                }
            }

            // Fall back to global flag (company_id = null)
            $globalFlag = FeatureFlag::whereNull('company_id')
                ->where('feature', $feature)
                ->first();

            return $globalFlag ? $globalFlag->is_enabled : true; // Default: enabled
        });
    }

    /**
     * Enable a feature for a company.
     */
    public function enable(string $feature, ?int $companyId = null, array $config = []): FeatureFlag
    {
        $flag = FeatureFlag::updateOrCreate(
            ['company_id' => $companyId, 'feature' => $feature],
            ['is_enabled' => true, 'config' => $config]
        );
        Cache::forget("ep_feature:{$feature}:{$companyId}");
        return $flag;
    }

    /**
     * Disable a feature for a company.
     */
    public function disable(string $feature, ?int $companyId = null): void
    {
        FeatureFlag::updateOrCreate(
            ['company_id' => $companyId, 'feature' => $feature],
            ['is_enabled' => false]
        );
        Cache::forget("ep_feature:{$feature}:{$companyId}");
    }

    /**
     * Get all feature flags for a company.
     */
    public function allForCompany(?int $companyId): array
    {
        $result = [];
        foreach (self::ALL_FEATURES as $feature) {
            $result[$feature] = $this->isEnabled($feature, $companyId);
        }
        return $result;
    }
}
