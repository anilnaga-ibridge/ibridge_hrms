<?php

namespace Tests\Feature\EnterpriseTasks;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PomodoroTest extends TestCase
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
    public function users_can_start_and_complete_pomodoro_sessions()
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

        // Start session
        $startResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/enterprise-tasks/pomodoro/start');
        $startResponse->assertStatus(201);
        $xid = $startResponse->json('xid');

        // Complete session
        $completeResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/enterprise-tasks/pomodoro/{$xid}/complete");
        $completeResponse->assertStatus(200);
        $this->assertTrue($completeResponse->json('completed'));
    }
}
