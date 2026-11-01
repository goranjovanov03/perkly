<?php

namespace Tests\Unit\Policies;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Policies\OrganizationPolicy;
use App\Policies\OrganizationMembershipPolicy;
use Illuminate\Http\Request;

class OrganizationPolicyTest extends TestCase
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

    protected function createRequestWithOrganization(Organization $organization): Request
    {
        $request = Request::create('/api/v1/org/current', 'GET');
        $request->attributes->set('current_organization', $organization);
        $request->merge(['current_organization' => $organization]);
        app()->instance('current_organization', $organization);
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        return $request;
    }

    protected function createRequestWithoutOrganization(): Request
    {
        $request = Request::create('/api/v1/org/current', 'GET');
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(null);

        return $request;
    }

    public function test_owner_can_view_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'owner', $organization);

        $request = $this->createRequestWithOrganization($organization);
        $request->setUserResolver(fn () => $user);

        $policy = new OrganizationPolicy();
        $result = $policy->view($user);

        $this->assertTrue($result);
    }

    public function test_admin_can_view_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'admin', $organization);

        $request = $this->createRequestWithOrganization($organization);
        $request->setUserResolver(fn () => $user);

        $policy = new OrganizationPolicy();
        $result = $policy->view($user);

        $this->assertTrue($result);
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

        $request = $this->createRequestWithOrganization($organization);
        $request->setUserResolver(fn () => $user);

        $policy = new OrganizationPolicy();
        $result = $policy->view($user);

        $this->assertTrue($result);
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

        $request = $this->createRequestWithOrganization($organization);
        $request->setUserResolver(fn () => $user);

        $policy = new OrganizationPolicy();
        $result = $policy->update($user);

        $this->assertTrue($result);
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

        $request = $this->createRequestWithOrganization($organization);
        $request->setUserResolver(fn () => $user);

        $policy = new OrganizationPolicy();
        $result = $policy->update($user);

        $this->assertFalse($result);
    }

    public function test_member_cannot_update_organization(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'member', $organization);

        $request = $this->createRequestWithOrganization($organization);
        $request->setUserResolver(fn () => $user);

        $policy = new OrganizationPolicy();
        $result = $policy->update($user);

        $this->assertFalse($result);
    }

    public function test_view_returns_false_without_current_organization(): void
    {
        $user = User::factory()->create();

        $request = $this->createRequestWithoutOrganization();
        $request->setUserResolver(fn () => $user);

        $policy = new OrganizationPolicy();
        $result = $policy->view($user);

        $this->assertFalse($result);
    }

    public function test_update_returns_false_without_current_organization(): void
    {
        $user = User::factory()->create();

        $request = $this->createRequestWithoutOrganization();
        $request->setUserResolver(fn () => $user);

        $policy = new OrganizationPolicy();
        $result = $policy->update($user);

        $this->assertFalse($result);
    }

    public function test_user_with_role_in_org_a_denied_in_org_b_for_view(): void
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

        $request = $this->createRequestWithOrganization($organizationB);
        $request->setUserResolver(fn () => $user);

        $policy = new OrganizationPolicy();
        $result = $policy->view($user);

        $this->assertFalse($result);
    }

    public function test_user_with_role_in_org_a_denied_in_org_b_for_update(): void
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

        $request = $this->createRequestWithOrganization($organizationB);
        $request->setUserResolver(fn () => $user);

        $policy = new OrganizationPolicy();
        $result = $policy->update($user);

        $this->assertFalse($result);
    }
}