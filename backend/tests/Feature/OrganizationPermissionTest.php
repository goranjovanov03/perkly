<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationMembership;

class OrganizationPermissionTest extends TestCase
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

    public function test_owner_can_update_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'owner', $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->patchJson('/api/v1/org/current', ['name' => 'Updated Name']);

        $response->assertStatus(200);
        $response->assertJsonPath('organization.name', 'Updated Name');
    }

    public function test_admin_cannot_update_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'admin', $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->patchJson('/api/v1/org/current', ['name' => 'Updated Name']);

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_member_cannot_list_members_without_permission(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/members');

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_member_with_permission_can_list_members(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'member', $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/members');

        $response->assertStatus(200);
        $response->assertJsonStructure(['members']);
    }

    public function test_role_in_org_a_gives_no_permission_in_org_b(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organizationA->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'owner', $organizationA);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organizationB->id])
            ->patchJson('/api/v1/org/current', ['name' => 'Updated Name']);

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_member_can_view_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'member', $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/current');

        $response->assertStatus(200);
        $response->assertJsonPath('organization.id', $organization->id);
    }

    public function test_admin_can_list_members(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'admin', $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/members');

        $response->assertStatus(200);
        $response->assertJsonStructure(['members']);
    }
}