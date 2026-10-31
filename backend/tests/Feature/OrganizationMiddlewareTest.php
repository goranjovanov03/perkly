<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationMembership;

class OrganizationMiddlewareTest extends TestCase
{
    public function test_member_gets_200(): void
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
            ->getJson('/api/v1/org/current');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'organization' => ['id', 'name', 'slug'],
        ]);
        $response->assertJsonPath('organization.id', $organization->id);
    }

    public function test_non_member_gets_403(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/current');

        $response->assertStatus(403);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id'],
        ]);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_missing_header_gets_403(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/org/current');

        $response->assertStatus(403);
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id'],
        ]);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_user_from_org_a_cannot_read_org_b(): void
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
        $response->assertJsonStructure([
            'error' => ['code', 'message', 'request_id'],
        ]);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_inactive_membership_gets_403(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/current');

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_invalid_organization_id_gets_403(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => 'invalid-id'])
            ->getJson('/api/v1/org/current');

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }
}