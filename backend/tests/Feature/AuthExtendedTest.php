<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\PersonalAccessToken;

class AuthExtendedTest extends TestCase
{
    public function test_me_returns_onboarded_at(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'onboarded_at' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me');

        $response->assertStatus(200);
        $this->assertArrayHasKey('onboarded_at', $response->json('user'));
        $this->assertNull($response->json('user.onboarded_at'));
    }

    public function test_me_returns_onboarded_at_when_set(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'onboarded_at' => now(),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me');

        $response->assertStatus(200);
        $this->assertArrayHasKey('onboarded_at', $response->json('user'));
        $this->assertEquals($user->onboarded_at->toISOString(), $response->json('user.onboarded_at'));
    }

    public function test_complete_onboarding_sets_onboarded_at(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'onboarded_at' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/onboarding/complete');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'onboarded_at'],
        ]);
        $response->assertJsonPath('user.onboarded_at', $user->fresh()->onboarded_at->toISOString());
        $this->assertNotNull($user->fresh()->onboarded_at);
    }

    public function test_complete_onboarding_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/me/onboarding/complete');

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_organizations_returns_only_active_memberships(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $orgActive = Organization::factory()->create(['name' => 'Active Org']);
        $orgInactive = Organization::factory()->create(['name' => 'Inactive Org']);
        $orgPending = Organization::factory()->create(['name' => 'Pending Org']);
        $orgNoMembership = Organization::factory()->create(['name' => 'No Membership Org']);

        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $orgActive->id,
            'status' => 'active',
        ]);
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $orgInactive->id,
            'status' => 'inactive',
        ]);
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $orgPending->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/me/organizations');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'organizations' => [
                '*' => ['id', 'name', 'slug'],
            ],
        ]);

        $orgIds = collect($response->json('organizations'))->pluck('id')->toArray();
        $this->assertContains($orgActive->id, $orgIds);
        $this->assertNotContains($orgInactive->id, $orgIds);
        $this->assertNotContains($orgPending->id, $orgIds);
        $this->assertNotContains($orgNoMembership->id, $orgIds);
    }

    public function test_organizations_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/me/organizations');

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_logout_all_revokes_all_tokens_and_sessions(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $token1 = $user->createToken('device1')->plainTextToken;
        $token2 = $user->createToken('device2')->plainTextToken;
        $token3 = $user->createToken('device3')->plainTextToken;

        $this->assertCount(3, PersonalAccessToken::where('tokenable_id', $user->id)->get());

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token1])
            ->postJson('/api/v1/auth/logout-all');

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Logged out from all devices');

        $this->assertCount(0, PersonalAccessToken::where('tokenable_id', $user->id)->get());
    }

    public function test_logout_all_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/auth/logout-all');

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_forgot_password_returns_202_always(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'existing@example.com',
        ]);

        $response->assertStatus(202);
        $response->assertJsonPath('message', 'If the email exists, a reset link has been sent.');
    }

    public function test_forgot_password_returns_202_for_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(202);
        $response->assertJsonPath('message', 'If the email exists, a reset link has been sent.');
    }

    public function test_forgot_password_rate_limited(): void
    {
        RateLimiter::clear('forgot-password:127.0.0.1');

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/v1/auth/forgot-password', [
                'email' => 'test@example.com',
            ]);
        }

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(429);
        $response->assertJsonPath('error.code', 'TOO_MANY_REQUESTS');
    }

    public function test_reset_password_works(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('oldpassword'),
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Password has been reset.');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_reset_password_revokes_all_tokens_and_sessions(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('oldpassword'),
        ]);

        $user->createToken('device1');
        $user->createToken('device2');

        $this->assertCount(2, PersonalAccessToken::where('tokenable_id', $user->id)->get());

        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $this->assertCount(0, PersonalAccessToken::where('tokenable_id', $user->id)->get());
    }

    public function test_reset_password_token_works_once(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('oldpassword'),
        ]);

        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'anotherpassword123',
            'password_confirmation' => 'anotherpassword123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $response->assertJsonPath('error.message', 'Invalid or expired reset token.');
    }

    public function test_reset_password_invalid_token_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('oldpassword'),
        ]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $response->assertJsonPath('error.message', 'Invalid or expired reset token.');
    }

    public function test_reset_password_expired_token_fails(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('oldpassword'),
        ]);

        $token = Password::createToken($user);

        \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update(['created_at' => now()->subHours(2)]);

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => $token,
            'email' => 'test@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_sanctum_token_expiration_30_days(): void
    {
        $this->assertEquals(43200, config('sanctum.expiration'));
    }
}