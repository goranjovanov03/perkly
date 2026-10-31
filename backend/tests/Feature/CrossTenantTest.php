<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationMembership;

class CrossTenantTest extends TestCase
{
    public function test_user_of_org_a_with_org_b_header_gets_403_on_org_current(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organizationA->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organizationB->id])
            ->getJson('/api/v1/org/current');

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_user_of_org_a_with_org_b_header_gets_403_on_org_members(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organizationA->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organizationB->id])
            ->getJson('/api/v1/org/members');

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_user_of_org_a_with_org_b_header_gets_403_on_patch_org_current(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organizationA->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organizationB->id])
            ->patchJson('/api/v1/org/current', ['name' => 'New Name']);

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_user_cannot_see_another_organizations_membership_list(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $userA->id,
            'organization_id' => $organizationA->id,
            'status' => 'active',
        ]);
        OrganizationMembership::factory()->create([
            'user_id' => $userB->id,
            'organization_id' => $organizationB->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($userA, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organizationB->id])
            ->getJson('/api/v1/org/members');

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_user_of_org_a_can_access_org_a(): void
    {
        $organizationA = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organizationA->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organizationA->id])
            ->getJson('/api/v1/org/current');

        $response->assertStatus(200);
        $response->assertJsonPath('organization.id', $organizationA->id);
    }
}