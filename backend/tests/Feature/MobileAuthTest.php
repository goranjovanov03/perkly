<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class MobileAuthTest extends TestCase
{
    public function test_token_issued_on_valid_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password123',
            'device_name' => 'iPhone 15',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'token',
        ]);
        $this->assertIsString($response->json('token'));
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_wrong_password_returns_401(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'wrongpassword',
            'device_name' => 'iPhone 15',
        ]);

        $response->assertStatus(401);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id'],
        ]);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_token_rate_limited(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
            ])->postJson('/api/v1/auth/token', [
                'email' => $user->email,
                'password' => 'wrongpassword',
                'device_name' => 'iPhone 15',
            ]);
        }

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'wrongpassword',
            'device_name' => 'iPhone 15',
        ]);

        $response->assertStatus(429);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id'],
        ]);
        $response->assertJsonPath('error.code', 'TOO_MANY_REQUESTS');
    }

    public function test_me_works_with_token(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password123',
            'device_name' => 'iPhone 15',
        ]);

        $token = $response->json('token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/me');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
        ]);
    }

    public function test_revoked_token_gets_401(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password123',
            'device_name' => 'iPhone 15',
        ]);

        $token = $response->json('token');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson('/api/v1/auth/token');

        $this->app['auth']->forgetGuards();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/me');

        $response->assertStatus(401);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id'],
        ]);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }
}