<?php

namespace Tests\Unit\EnterpriseTasks;

use Tests\TestCase;
use App\Services\EnterpriseTasks\GamificationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GamificationServiceTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_starts_and_completes_pomodoro_sessions()
    {
        $service = $this->app->make(GamificationService::class);
        
        $session = $service->startPomodoroSession(1);
        $this->assertNotNull($session);
        $this->assertFalse($session->completed);

        $completed = $service->completePomodoroSession($session->id, 1);
        $this->assertTrue($completed->completed);
    }
}
