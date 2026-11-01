<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Notification;
use App\Models\DeviceInstallation;
use App\Models\NotificationPreference;
use App\Enums\TargetType;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class NotificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        Log::spy();
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

    public function test_list_notifications_requires_auth(): void
    {
        $response = $this->withHeaders(['X-Organization-Id' => '01HX7Z9K9V9V9V9V9V9V9V9V9V'])
            ->getJson('/api/v1/org/notifications');

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_list_notifications_requires_organization_header(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/org/notifications');

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_list_notifications_returns_empty_for_user_with_no_notifications(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/notifications');

        $response->assertStatus(200);
        $response->assertJsonStructure(['notifications', 'next_cursor']);
        $this->assertEmpty($response->json('notifications'));
    }

    public function test_list_notifications_returns_user_notifications(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization);

        Notification::factory()->count(3)->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/notifications');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('notifications'));
    }

    public function test_list_notifications_scoped_to_organization(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organizationA);

        Notification::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organizationA->id,
        ]);
        Notification::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organizationB->id,
        ]);
        Notification::factory()->create([
            'user_id' => $user->id,
            'organization_id' => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organizationA->id])
            ->getJson('/api/v1/org/notifications');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('notifications'));
    }

    public function test_list_notifications_cross_org_returns_403(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organizationA);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organizationB->id])
            ->getJson('/api/v1/org/notifications');

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_list_notifications_unread_filter(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization);

        Notification::factory()->unread()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);
        Notification::factory()->read()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/notifications?unread=true');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('notifications'));
        $this->assertNull($response->json('notifications.0.read_at'));
    }

    public function test_list_notifications_cursor_pagination(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization);

        Notification::factory()->count(5)->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/notifications');

        $response->assertStatus(200);
        $notifications = $response->json('notifications');
        $this->assertLessThanOrEqual(20, count($notifications));
        $this->assertNotNull($response->json('next_cursor'));
    }

    public function test_mark_notification_as_read(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization);

        $notification = Notification::factory()->unread()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->postJson("/api/v1/org/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $this->assertEquals('Notification marked as read', $response->json('message'));

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }

    public function test_mark_notification_as_read_cross_user_404(): void
    {
        $organization = Organization::factory()->create();
        $user1 = User::factory()->create(['password' => Hash::make('password123')]);
        $user2 = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user1, $organization);
        $this->actingAsUserInOrg($user2, $organization);

        $notification = Notification::factory()->unread()->create([
            'user_id' => $user2->id,
            'organization_id' => $organization->id,
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->postJson("/api/v1/org/notifications/{$notification->id}/read");

        $response->assertStatus(404);
    }

    public function test_mark_all_notifications_as_read(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization);

        Notification::factory()->unread()->count(3)->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->postJson('/api/v1/org/notifications/read-all');

        $response->assertStatus(200);
        $this->assertEquals('All notifications marked as read', $response->json('message'));

        $this->assertEquals(3, Notification::where('user_id', $user->id)
            ->where('organization_id', $organization->id)
            ->whereNotNull('read_at')
            ->count());
    }
}