<?php

namespace App\Console\Commands;

use App\Services\PerformanceService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CalculatePerformanceScores extends Command
{
    protected $signature = 'performance:calculate {--month= : Month to calculate for (1-12)} {--year= : Year to calculate for}';
    protected $description = 'Calculate performance scores for all active employees';

    public function handle(PerformanceService $performanceService)
    {
        $month = $this->option('month') ? (int) $this->option('month') : Carbon::now()->month;
        $year = $this->option('year') ? (int) $this->option('year') : Carbon::now()->year;

        $this->info("Calculating performance scores for {$month}/{$year}...");

        $performanceService->calculateForAll($month, $year);

        $this->info('Performance scores calculated successfully!');

        return 0;
    }
}
