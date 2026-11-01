<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsTest extends TestCase
{
    public function test_preflight_from_allowed_origin_returns_correct_headers(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'Content-Type',
        ])->options('/api/v1/auth/login');

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
        $response->assertHeader('Access-Control-Allow-Methods', 'POST');
        $response->assertHeader('Access-Control-Allow-Headers', 'Content-Type');
        }

    public function test_preflight_from_unknown_origin_returns_no_allow_origin(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://evil.com',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'Content-Type',
        ])->options('/api/v1/auth/login');

        $response->assertStatus(204);
        $response->assertHeaderMissing('Access-Control-Allow-Origin');
        $response->assertHeaderMissing('Access-Control-Allow-Credentials');
    }

    public function test_preflight_from_127_0_0_1_returns_correct_headers(): void
    {
        $response = $this->withHeaders([
            'Origin' => 'http://127.0.0.1:5173',
            'Access-Control-Request-Method' => 'GET',
            'Access-Control-Request-Headers' => 'Authorization',
        ])->options('/api/v1/me');

        $response->assertStatus(204);
        $response->assertHeader('Access-Control-Allow-Origin', 'http://127.0.0.1:5173');
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
    }
}