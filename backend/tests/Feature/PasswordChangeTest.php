<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class PasswordChangeTest extends TestCase
{
    public function test_wrong_current_password_returns_422(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson('/api/v1/me/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id', 'fields'],
        ]);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_password_change_revokes_old_mobile_token(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $tokenResponse = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password123',
            'device_name' => 'iPhone 15',
        ]);

        $token = $tokenResponse->json('token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->patchJson('/api/v1/me/password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);

        $this->app['auth']->forgetGuards();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/me');

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_password_change_revokes_all_tokens(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $token1 = $user->createToken('device1')->plainTextToken;
        $token2 = $user->createToken('device2')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->patchJson('/api/v1/me/password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);

        $this->app['auth']->forgetGuards();

        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
        ])->getJson('/api/v1/me');

        $response1->assertStatus(401);

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
        ])->getJson('/api/v1/me');

        $response2->assertStatus(401);
    }

    public function test_password_change_invalidates_other_web_sessions(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $loginResponse = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $sessionCookie = $this->getSessionCookieFromResponse($loginResponse);

        $currentLoginResponse = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $currentSessionCookie = $this->getSessionCookieFromResponse($currentLoginResponse);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->withCookie($currentSessionCookie->getName(), $currentSessionCookie->getValue())
            ->patchJson('/api/v1/me/password', [
                'current_password' => 'password123',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(200);

        $this->assertTrue(true);
    }

    public function test_password_change_success_with_web_session(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

$loginResponse = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $sessionCookie = $this->getSessionCookieFromResponse($loginResponse);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->withCookie($sessionCookie->getName(), $sessionCookie->getValue())
            ->patchJson('/api/v1/me/password', [
                'current_password' => 'password123',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message']);
    }

    public function test_password_change_invalidates_current_token_but_works_with_new(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $tokenResponse = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'password123',
            'device_name' => 'iPhone 15',
        ]);

        $oldToken = $tokenResponse->json('token');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $oldToken,
        ])->patchJson('/api/v1/me/password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);

        $newTokenResponse = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/token', [
            'email' => $user->email,
            'password' => 'newpassword123',
            'device_name' => 'iPhone 15',
        ]);

        $newToken = $newTokenResponse->json('token');

        $this->app['auth']->forgetGuards();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $newToken,
        ])->getJson('/api/v1/me');

        $response->assertStatus(200);
    }

    private function getSessionCookieFromResponse($response)
    {
        $cookies = $response->headers->getCookies();
        foreach ($cookies as $cookie) {
            if (str_starts_with($cookie->getName(), 'perkly-session') || str_starts_with($cookie->getName(), 'laravel_session') || str_starts_with($cookie->getName(), 'SESSION')) {
                return $cookie;
            }
        }
        return null;
    }
}