<?php

namespace Tests\Unit\EnterpriseTasks;

use Tests\TestCase;
use App\Services\EnterpriseTasks\AITaskAssistantService;

class AITaskAssistantServiceTest extends TestCase
{
    /** @test */
    public function it_suggests_priority_based_on_critical_keywords()
    {
        $service = new AITaskAssistantService();

        $priority = $service->suggestPriority('URGENT: production database server down immediately', 'blocker issue');
        $this->assertEquals('P1', $priority);

        $priorityLow = $service->suggestPriority('clean up local temp folders', 'nice to have task');
        $this->assertEquals('P4', $priorityLow);
    }

    /** @test */
    public function it_generates_rules_based_subtasks()
    {
        $service = new AITaskAssistantService();

        $subtasks = $service->generateSubtasks('Fix bug in user login auth flow');
        $this->assertContains('Reproduce issue', $subtasks);
        $this->assertContains('Apply code fix', $subtasks);
    }
}
