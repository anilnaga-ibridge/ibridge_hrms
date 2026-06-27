<?php

namespace App\Jobs\EnterpriseTasks;

use App\Models\Project;
use App\Models\Company;
use App\Services\EnterpriseTasks\MetricsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(MetricsService $metricsService): void
    {
        $projects = Project::where('status', 'active')->get();
        foreach ($projects as $project) {
            $metricsService->calculateProjectMetrics($project->id);
            $metricsService->calculateTaskMetrics($project->company_id, $project->id);
        }

        $companies = Company::all();
        foreach ($companies as $company) {
            $metricsService->calculateTaskMetrics($company->id, null);
        }
    }
}
