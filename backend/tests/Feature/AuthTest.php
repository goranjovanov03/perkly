<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    public function test_valid_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
        ]);
        $this->assertAuthenticated();
    }

    public function test_wrong_password_returns_401(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id'],
        ]);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
        $this->assertGuest();
    }

    public function test_login_rate_limited(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->withHeaders([
                'X-Requested-With' => 'XMLHttpRequest',
            ])->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'wrongpassword',
            ]);
        }

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(429);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id'],
        ]);
        $response->assertJsonPath('error.code', 'TOO_MANY_REQUESTS');
    }

    public function test_logout(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        $cookies = $response->headers->getCookies();
        $sessionCookieName = config('session.cookie');
        $sessionCookie = null;
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === $sessionCookieName) {
                $sessionCookie = $cookie;
                break;
            }
        }

        $this->assertNotNull($sessionCookie, 'Session cookie not found in response');

        $controller = new \App\Http\Controllers\AuthController();

        $logoutRequest = \Illuminate\Http\Request::create('/api/v1/auth/logout', 'POST', [], [
            'Cookie' => $sessionCookieName . '=' . $sessionCookie->getValue(),
        ], [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        $logoutRequest->setLaravelSession(app('session')->driver());
        $logoutRequest->session()->setId($sessionCookie->getValue());

        $logoutResponse = $controller->logout($logoutRequest);

        $this->assertEquals(200, $logoutResponse->getStatusCode());

        $setCookieHeader = $logoutResponse->headers->get('Set-Cookie');
        $this->assertNotEmpty($setCookieHeader, 'No Set-Cookie header in logout response');

        $foundExpiredCookie = false;
        if (is_array($setCookieHeader)) {
            foreach ($setCookieHeader as $header) {
                if (str_starts_with($header, $sessionCookieName . '=') && str_contains($header, 'expires=') && (str_contains($header, 'Max-Age=0') || str_contains($header, 'Max-Age=-'))) {
                    $foundExpiredCookie = true;
                    break;
                }
            }
        } else {
            $header = $setCookieHeader;
            if (str_starts_with($header, $sessionCookieName . '=') && str_contains($header, 'expires=') && (str_contains($header, 'Max-Age=0') || str_contains($header, 'Max-Age=-'))) {
                $foundExpiredCookie = true;
            }
        }

        $this->assertTrue($foundExpiredCookie, 'Logout did not set expired session cookie');

        $app = $this->app;
        $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

        $meRequest = \Illuminate\Http\Request::create('/api/v1/me', 'GET', [], [
            'Cookie' => $sessionCookieName . '=' . $sessionCookie->getValue(),
        ], [], [
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);

        $meResponse = $kernel->handle($meRequest);

        $this->assertEquals(401, $meResponse->getStatusCode());
        $content = json_decode($meResponse->getContent(), true);
        $this->assertEquals('UNAUTHENTICATED', $content['error']['code'] ?? null);
    }

    public function test_me_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id'],
        ]);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_me_returns_user(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
        ]);
    }
}