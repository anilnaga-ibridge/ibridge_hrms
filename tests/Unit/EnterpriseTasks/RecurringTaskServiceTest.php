<?php

namespace Tests\Unit\EnterpriseTasks;

use Tests\TestCase;
use App\Services\EnterpriseTasks\RecurringTaskService;
use Carbon\Carbon;

class RecurringTaskServiceTest extends TestCase
{
    /** @test */
    public function it_calculates_correct_daily_recurrence()
    {
        $service = $this->app->make(RecurringTaskService::class);
        
        $baseDate = Carbon::parse('2026-06-26 12:00:00');
        $next = $service->calculateNextRun('daily', $baseDate, 1);

        $this->assertEquals('2026-06-27', $next->toDateString());
    }

    /** @test */
    public function it_calculates_correct_weekly_recurrence()
    {
        $service = $this->app->make(RecurringTaskService::class);
        
        $baseDate = Carbon::parse('2026-06-26 12:00:00'); // Friday
        $next = $service->calculateNextRun('weekly', $baseDate, 1, 'monday,wednesday');

        // Next Monday after 2026-06-26 is 2026-06-29
        $this->assertEquals('2026-06-29', $next->toDateString());
    }
}
