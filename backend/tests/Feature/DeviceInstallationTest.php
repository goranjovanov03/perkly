<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\DeviceInstallation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DeviceInstallationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    protected function assignRoleToUserForOrganization(User $user, string $roleName, Organization $organization): void
    {
        $Role = app(config('permission.models.role'));
        $role = $Role::where('name', $roleName)->where('guard_name', 'sanctum')->first();
        $user->roles()->attach($role, ['team_id' => $organization->id]);
    }

    protected function actingAsUserInOrg(User $user, Organization $organization): void
    {
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'member', $organization);
    }

    public function test_register_device_requires_auth(): void
    {
        $response = $this->postJson('/api/v1/me/devices', [
            'platform' => 'android',
            'push_token' => 'token-123',
        ]);

        $response->assertStatus(401);
    }

    public function test_register_device_creates_new_installation(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123'), 'locale' => 'mk']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/devices', [
                'platform' => 'android',
                'push_token' => 'token-123',
                'app_version' => '1.0.0',
                'locale' => 'mk',
            ]);

        $response->assertStatus(201);
        $this->assertEquals('android', $response->json('installation.platform'));
        $this->assertEquals('token-123', $response->json('installation.push_token'));
        $this->assertEquals('1.0.0', $response->json('installation.app_version'));
        $this->assertEquals('mk', $response->json('installation.locale'));
        $this->assertNull($response->json('installation.revoked_at'));

        $this->assertDatabaseCount('device_installations', 1);
    }

    public function test_register_device_idempotent_updates_existing(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123'), 'locale' => 'mk']);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/devices', [
                'platform' => 'android',
                'push_token' => 'token-123',
                'app_version' => '1.0.0',
            ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/devices', [
                'platform' => 'ios',
                'push_token' => 'token-123',
                'app_version' => '2.0.0',
            ]);

        $response->assertStatus(201);
        $this->assertEquals('ios', $response->json('installation.platform'));
        $this->assertEquals('2.0.0', $response->json('installation.app_version'));

        $this->assertDatabaseCount('device_installations', 1);
    }

    public function test_register_device_validation_rejects_invalid_platform(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/devices', [
                'platform' => 'windows',
                'push_token' => 'token-123',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_register_device_validation_rejects_missing_push_token(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/devices', [
                'platform' => 'android',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_register_device_defaults_locale_from_user(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123'), 'locale' => 'en']);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/me/devices', [
                'platform' => 'android',
                'push_token' => 'token-123',
            ]);

        $response->assertStatus(201);
        $this->assertEquals('en', $response->json('installation.locale'));
    }

    public function test_revoke_device_requires_auth(): void
    {
        $response = $this->deleteJson('/api/v1/me/devices/01HX7Z9K9V9V9V9V9V9V9V9V9V');

        $response->assertStatus(401);
    }

    public function test_revoke_device_revokes_own_installation(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $installation = DeviceInstallation::factory()->create([
            'user_id' => $user->id,
            'push_token' => 'token-123',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/me/devices/{$installation->id}");

        $response->assertStatus(200);
        $this->assertEquals('Device installation revoked', $response->json('message'));

        $installation->refresh();
        $this->assertNotNull($installation->revoked_at);
    }

    public function test_revoke_device_cross_user_404(): void
    {
        $user1 = User::factory()->create(['password' => Hash::make('password123')]);
        $user2 = User::factory()->create(['password' => Hash::make('password123')]);
        $installation = DeviceInstallation::factory()->create([
            'user_id' => $user2->id,
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->deleteJson("/api/v1/me/devices/{$installation->id}");

        $response->assertStatus(404);
    }

    public function test_revoke_device_not_found_returns_404(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson('/api/v1/me/devices/' . Str::ulid());

        $response->assertStatus(404);
    }

    public function test_logout_revokes_device_installation(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $installation = DeviceInstallation::factory()->create([
            'user_id' => $user->id,
            'push_token' => 'token-123',
        ]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/logout');

        $installation->refresh();
        $this->assertNotNull($installation->revoked_at);
    }
}