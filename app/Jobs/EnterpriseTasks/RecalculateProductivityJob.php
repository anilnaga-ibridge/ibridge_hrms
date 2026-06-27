<?php

namespace App\Jobs\EnterpriseTasks;

use App\Models\Company;
use App\Services\EnterpriseTasks\ProductivityService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateProductivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ProductivityService $productivityService): void
    {
        $today = Carbon::today();
        $companies = Company::all();

        foreach ($companies as $company) {
            $productivityService->recalculateCompanyProductivity($company->id, $today->year, $today->month);
        }
    }
}
