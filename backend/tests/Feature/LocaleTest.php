<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LocaleTest extends TestCase
{
    public function test_update_locale_to_mk(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'locale' => 'en',
        ]);

        $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response = $this->patchJson('/api/v1/me/locale', [
            'locale' => 'mk',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.locale', 'mk');

        $user->refresh();
        $this->assertEquals('mk', $user->locale);
    }

    public function test_update_locale_to_en(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'locale' => 'mk',
        ]);

        $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response = $this->patchJson('/api/v1/me/locale', [
            'locale' => 'en',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('user.locale', 'en');

        $user->refresh();
        $this->assertEquals('en', $user->locale);
    }

    public function test_update_locale_validation_rejects_invalid(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response = $this->patchJson('/api/v1/me/locale', [
            'locale' => 'fr',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id', 'fields'],
        ]);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_update_locale_requires_auth(): void
    {
        $response = $this->patchJson('/api/v1/me/locale', [
            'locale' => 'mk',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_user_default_locale_is_mk(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $this->assertEquals('mk', $user->locale);
    }
}