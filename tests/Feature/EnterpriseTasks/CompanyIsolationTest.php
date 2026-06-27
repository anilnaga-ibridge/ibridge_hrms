<?php

namespace Tests\Feature\EnterpriseTasks;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\EnterpriseTasks\Task;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CompanyIsolationTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);
    }

    /** @test */
    public function users_cannot_view_tasks_from_another_company()
    {
        // Company A & User A
        $companyA = Company::create([
            'name' => 'Company A',
            'email' => 'a-' . uniqid() . '@example.com',
            'timezone' => 'UTC',
            'status' => 'active'
        ]);
        
        $userA = new User();
        $userA->company_id = $companyA->id;
        $userA->name = 'User A';
        $userA->email = 'usera-' . uniqid() . '@example.com';
        $userA->password = bcrypt('password');
        $userA->save();

        // Company B & Task B
        $companyB = Company::create([
            'name' => 'Company B',
            'email' => 'b-' . uniqid() . '@example.com',
            'timezone' => 'UTC',
            'status' => 'active'
        ]);
        
        $taskB = Task::create([
            'company_id' => $companyB->id,
            'title' => 'Company B Secret Task',
            'status' => 'pending',
            'priority' => 'P3',
            'task_number' => 'TST-0002'
        ]);

        $token = auth('api')->login($userA);

        // Verify taskB is not returned in listing for userA
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/enterprise-tasks/tasks');
        
        $response->assertStatus(200);
        $response->assertJsonMissing(['title' => 'Company B Secret Task']);
    }
}
