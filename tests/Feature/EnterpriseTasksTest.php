<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Project;
use App\Models\EnterpriseTasks\Task;
use App\Models\EnterpriseTasks\Dependency;
use App\Models\EnterpriseTasks\AutomationRule;
use App\Services\EnterpriseTasks\DependencyService;
use App\Services\EnterpriseTasks\ProductivityService;
use App\Services\EnterpriseTasks\AutomationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Exception;

class EnterpriseTasksTest extends TestCase
{
    use DatabaseTransactions;

    protected DependencyService $dependencyService;
    protected ProductivityService $productivityService;
    protected AutomationService $automationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dependencyService = app(DependencyService::class);
        $this->productivityService = app(ProductivityService::class);
        $this->automationService = app(AutomationService::class);
    }

    public function test_self_dependency_is_blocked()
    {
        $company = Company::first() ?? Company::factory()->create();
        $project = Project::create([
            'company_id' => $company->id,
            'name' => 'Test Project',
            'visibility' => 'public',
            'status' => 'active',
            'start_date' => now()->toDateString()
        ]);

        $task = Task::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'task_number' => 'TST-0001',
            'title' => 'Task A',
            'status' => 'pending',
            'priority' => 'P3'
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("A task cannot depend on itself");

        $this->dependencyService->createDependency(
            $company->id,
            $project->id,
            $task->id,
            $task->id,
            'finish_to_start',
            0
        );
    }

    public function test_circular_dependency_is_blocked()
    {
        $company = Company::first() ?? Company::factory()->create();
        $project = Project::create([
            'company_id' => $company->id,
            'name' => 'Test Project',
            'visibility' => 'public',
            'status' => 'active',
            'start_date' => now()->toDateString()
        ]);

        $taskA = Task::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'task_number' => 'TST-0001',
            'title' => 'Task A',
            'status' => 'pending',
            'priority' => 'P3'
        ]);

        $taskB = Task::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'task_number' => 'TST-0002',
            'title' => 'Task B',
            'status' => 'pending',
            'priority' => 'P3'
        ]);

        // A depends on B
        $this->dependencyService->createDependency(
            $company->id,
            $project->id,
            $taskA->id,
            $taskB->id,
            'finish_to_start',
            0
        );

        // B depends on A should trigger circular dependency block
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("circular dependency loop");

        $this->dependencyService->createDependency(
            $company->id,
            $project->id,
            $taskB->id,
            $taskA->id,
            'finish_to_start',
            0
        );
    }

    public function test_dependency_status_transitions()
    {
        $company = Company::first() ?? Company::factory()->create();
        $project = Project::create([
            'company_id' => $company->id,
            'name' => 'Test Project',
            'visibility' => 'public',
            'status' => 'active',
            'start_date' => now()->toDateString()
        ]);

        $taskA = Task::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'task_number' => 'TST-0001',
            'title' => 'Task A',
            'status' => 'pending',
            'priority' => 'P3'
        ]);

        $taskB = Task::create([
            'company_id' => $company->id,
            'project_id' => $project->id,
            'task_number' => 'TST-0002',
            'title' => 'Task B',
            'status' => 'pending',
            'priority' => 'P3'
        ]);

        // B depends on A (Finish to Start)
        $this->dependencyService->createDependency(
            $company->id,
            $project->id,
            $taskB->id,
            $taskA->id,
            'finish_to_start',
            0
        );

        // Verify B cannot start if A is pending
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Blocked by dependency");
        $this->dependencyService->validateStatusTransitions($taskB, 'in_progress');
    }

    public function test_productivity_score_calculations()
    {
        $company = Company::first() ?? Company::factory()->create();
        $user = User::first() ?? User::factory()->create();

        // Calculate score with no tasks (should yield base 100)
        $score = $this->productivityService->calculateUserScore($company->id, $user->id, 2026, 6);
        $this->assertEquals(100.0, $score['final_score']);
    }
}
