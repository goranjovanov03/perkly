<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

class CsrfTest extends TestCase
{
    public function test_csrf_cookie_then_login_succeeds(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $csrfResponse = $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ])->get('/sanctum/csrf-cookie');

        $csrfResponse->assertStatus(204);

        $csrfToken = null;
        foreach ($csrfResponse->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'XSRF-TOKEN') {
                $csrfToken = $cookie->getValue();
                break;
            }
        }

        $this->assertNotNull($csrfToken, 'XSRF-TOKEN cookie not set');

        $loginResponse = $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
            'X-XSRF-TOKEN' => urldecode($csrfToken),
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200);
        $loginResponse->assertJsonStructure([
            'user' => ['id', 'name', 'email'],
        ]);
        $this->assertAuthenticated();
    }

    public function test_csrf_error_format_matches_existing_error_format(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id'],
        ]);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }
}