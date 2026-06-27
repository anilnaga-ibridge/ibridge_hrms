<?php

namespace Tests\Feature\EnterpriseTasks;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class QuickAddTest extends TestCase
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
    public function it_creates_task_via_quick_add_endpoint()
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

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/enterprise-tasks/tasks/quick-create', [
            'text' => 'Deploy security updates tomorrow #P1 @ops',
        ]);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'title' => 'Deploy security updates',
            'priority' => 'P1'
        ]);
    }
}
