<?php

namespace Tests\Feature;

use Tests\TestCase;

class RequestIdMiddlewareTest extends TestCase
{
    public function test_request_id_is_generated_when_not_provided(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response->assertStatus(200);
        $response->assertHeader('X-Request-Id');
        $this->assertNotEmpty($response->header('X-Request-Id', ''));
    }

    public function test_request_id_is_reused_when_provided(): void
    {
        $requestId = 'test-request-id-123';
        $response = $this->getJson('/api/v1/health', ['X-Request-Id' => $requestId]);

        $response->assertStatus(200);
        $response->assertHeader('X-Request-Id', $requestId);
    }
}