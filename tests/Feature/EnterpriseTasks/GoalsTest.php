<?php

namespace Tests\Feature\EnterpriseTasks;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GoalsTest extends TestCase
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
    public function users_can_create_and_fetch_goals()
    {
        $company = Company::first() ?? Company::create([
            'name' => 'Test Company',
            'email' => 'test@example.com',
            'timezone' => 'UTC',
            'status' => 'active'
        ]);

        $user = new User();
        $user->company_id = $company->id;
        $user->name = 'Test User';
        $user->email = 'test-' . uniqid() . '@example.com';
        $user->password = bcrypt('password');
        $user->save();

        $token = auth('api')->login($user);

        // Create
        $createResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/enterprise-tasks/goals', [
            'name' => 'Complete 5 major sprint deliverables',
            'goal_type' => 'tasks_completed',
            'target' => 5,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString()
        ]);

        $createResponse->assertStatus(201);

        // Fetch
        $getResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/enterprise-tasks/goals');
        $getResponse->assertStatus(200);
        $getResponse->assertJsonFragment(['name' => 'Complete 5 major sprint deliverables']);
    }
}
