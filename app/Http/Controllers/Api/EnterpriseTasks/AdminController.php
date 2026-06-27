<?php

namespace App\Http\Controllers\Api\EnterpriseTasks;

use App\Http\Controllers\Controller;
use App\Services\EnterpriseTasks\ObservabilityService;
use App\Services\EnterpriseTasks\FeatureFlagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    protected FeatureFlagService $featureFlagService;

    public function __construct(FeatureFlagService $featureFlagService)
    {
        $this->featureFlagService = $featureFlagService;
    }

    /**
     * Get recent observability logs.
     */
    public function getLogs(Request $request)
    {
        $companyId = company()->id;
        $limit = $request->query('limit', 50);

        $logs = ObservabilityService::recentLogs($companyId, $limit);

        return response()->json($logs);
    }

    /**
     * Get error summary metrics.
     */
    public function getErrorSummary(Request $request)
    {
        $companyId = company()->id;
        $summary = ObservabilityService::errorSummary($companyId);

        return response()->json($summary);
    }

    /**
     * Get all feature flags configuration.
     */
    public function getFeatureFlags(Request $request)
    {
        $companyId = company()->id;
        $flags = $this->featureFlagService->allForCompany($companyId);

        return response()->json($flags);
    }

    /**
     * Toggle a specific feature flag.
     */
    public function toggleFeatureFlag(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'feature' => 'required|string|in:' . implode(',', FeatureFlagService::ALL_FEATURES),
            'is_enabled' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $companyId = company()->id;
        $feature = $request->feature;
        $isEnabled = $request->is_enabled;

        if ($isEnabled) {
            $this->featureFlagService->enable($feature, $companyId);
        } else {
            $this->featureFlagService->disable($feature, $companyId);
        }

        return response()->json([
            'success' => true,
            'feature' => $feature,
            'is_enabled' => $isEnabled
        ]);
    }
}
