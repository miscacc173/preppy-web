<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_bearer_token_is_revoked_on_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'logout@example.test',
            'password' => 'Password@123',
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password@123',
            'device_name' => 'logout-test',
        ])->assertOk();

        $token = $login->json('token');

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJson(['message' => 'Logged out']);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_new_login_revokes_every_previous_login_session(): void
    {
        $user = User::factory()->create([
            'email' => 'single-login@example.test',
            'password' => 'Password@123',
        ]);

        $firstToken = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password@123',
            'device_name' => 'first-device',
        ])->assertOk()->json('token');

        $this->withToken($firstToken)->getJson('/api/v1/auth/me')->assertOk();

        $secondToken = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password@123',
            'device_name' => 'second-device',
        ])->assertOk()->json('token');

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->withToken($firstToken)->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->withToken($secondToken)->getJson('/api/v1/auth/me')->assertOk();
    }

    public function test_api_login_revokes_existing_browser_sessions(): void
    {
        $user = User::factory()->create([
            'email' => 'browser-session@example.test',
            'password' => 'Password@123',
        ]);

        \Illuminate\Support\Facades\DB::table('sessions')->insert([
            'id' => 'previous-browser-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password@123',
        ])->assertOk();

        $this->assertDatabaseMissing('sessions', ['id' => 'previous-browser-session']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }
}
