<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\NotificationPreference;
use Illuminate\Support\Facades\Hash;

class NotificationPreferenceTest extends TestCase
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

    public function test_get_preferences_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/me/notification-preferences');

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_get_preferences_returns_empty_when_none_set(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/me/notification-preferences');

        $response->assertStatus(200);
        $response->assertJsonStructure(['preferences']);
        $this->assertEmpty($response->json('preferences'));
    }

    public function test_get_preferences_scoped_to_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization);

        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'channel' => 'push',
            'type' => 'notification.test',
            'enabled' => true,
        ]);
        NotificationPreference::factory()->create([
            'user_id' => $user->id,
            'organization_id' => null,
            'channel' => 'email',
            'type' => 'notification.other',
            'enabled' => false,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/me/notification-preferences');

        $response->assertStatus(200);
        $prefs = $response->json('preferences');
        $this->assertTrue($prefs['push']['notification.test']);
        $this->assertFalse($prefs['email']['notification.other']);
    }

    public function test_update_preferences_requires_auth(): void
    {
        $response = $this->putJson('/api/v1/me/notification-preferences', [
            'preferences' => [
                ['channel' => 'push', 'type' => 'notification.test', 'enabled' => true],
            ],
        ]);

        $response->assertStatus(401);
    }

    public function test_update_preferences_creates_and_updates(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->putJson('/api/v1/me/notification-preferences', [
                'preferences' => [
                    ['channel' => 'push', 'type' => 'notification.test', 'enabled' => true],
                    ['channel' => 'email', 'type' => 'notification.test', 'enabled' => false],
                    ['channel' => 'in_app', 'type' => 'notification.test', 'enabled' => true],
                ],
            ]);

        $response->assertStatus(200);
        $prefs = $response->json('preferences');
        $this->assertTrue($prefs['push']['notification.test']);
        $this->assertFalse($prefs['email']['notification.test']);
        $this->assertTrue($prefs['in_app']['notification.test']);

        $this->assertDatabaseCount('notification_preferences', 3);
    }

    public function test_update_preferences_validation_rejects_invalid_channel(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->putJson('/api/v1/me/notification-preferences', [
                'preferences' => [
                    ['channel' => 'sms', 'type' => 'notification.test', 'enabled' => true],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_update_preferences_validation_requires_enabled(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->putJson('/api/v1/me/notification-preferences', [
                'preferences' => [
                    ['channel' => 'push', 'type' => 'notification.test'],
                ],
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }
}