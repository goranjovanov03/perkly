<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiAuthFormatTest extends TestCase
{
    public function test_unauthenticated_api_request_without_accept_header_returns_401(): void
    {
        $response = $this->get('/api/v1/me');

        $response->assertStatus(401);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id'],
        ]);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }
}