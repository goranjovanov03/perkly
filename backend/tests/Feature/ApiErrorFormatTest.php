<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class ApiErrorFormatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api')->prefix('api')->group(function () {
            Route::get('/v1/trigger-500', function () {
                throw new \Exception('Test 500 error');
            });

            Route::post('/v1/validate-test', function (Request $request) {
                $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email',
                ]);

                return response()->json(['success' => true]);
            });

            Route::get('/forbidden-test', function () {
                Gate::authorize('forbidden-action');

                return response()->json(['success' => true]);
            })->middleware('auth:sanctum');
        });
    }

    public function test_401_returns_consistent_json(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id'],
        ]);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_403_returns_consistent_json(): void
    {
        Gate::define('forbidden-action', fn () => false);

        $user = \App\Models\User::factory()->create();
        $response = $this->actingAs($user, 'sanctum')->getJson('/api/forbidden-test');

        $response->assertStatus(403);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id'],
        ]);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_422_returns_consistent_json(): void
    {
        $response = $this->postJson('/api/v1/validate-test', []);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id', 'fields'],
        ]);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $response->assertJsonStructure([
            'error' => ['fields' => ['name', 'email']],
        ]);
    }

    public function test_404_returns_consistent_json(): void
    {
        $response = $this->getJson('/api/v1/nonexistent');

        $response->assertStatus(404);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id'],
        ]);
        $response->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_500_returns_consistent_json(): void
    {
        $response = $this->getJson('/api/v1/trigger-500');

        $response->assertStatus(500);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id'],
        ]);
        $response->assertJsonPath('error.code', 'SERVER_ERROR');
    }
}