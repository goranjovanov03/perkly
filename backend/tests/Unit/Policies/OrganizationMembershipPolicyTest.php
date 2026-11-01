<?php

namespace Tests\Unit\Policies;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Policies\OrganizationMembershipPolicy;
use Illuminate\Http\Request;

class OrganizationMembershipPolicyTest extends TestCase
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
        $request = Request::create('/api/v1/org/members', 'GET');
        $request->attributes->set('current_organization', $organization);
        $request->merge(['current_organization' => $organization]);
        app()->instance('current_organization', $organization);
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        return $request;
    }

    protected function createRequestWithoutOrganization(): Request
    {
        $request = Request::create('/api/v1/org/members', 'GET');
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(null);

        return $request;
    }

    public function test_owner_can_view_any_members(): void
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

        $policy = new OrganizationMembershipPolicy();
        $result = $policy->viewAny($user);

        $this->assertTrue($result);
    }

    public function test_admin_can_view_any_members(): void
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

        $policy = new OrganizationMembershipPolicy();
        $result = $policy->viewAny($user);

        $this->assertTrue($result);
    }

    public function test_member_can_view_any_members(): void
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

        $policy = new OrganizationMembershipPolicy();
        $result = $policy->viewAny($user);

        $this->assertTrue($result);
    }

    public function test_owner_can_manage_members(): void
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

        $policy = new OrganizationMembershipPolicy();
        $result = $policy->manage($user);

        $this->assertTrue($result);
    }

    public function test_admin_can_manage_members(): void
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

        $policy = new OrganizationMembershipPolicy();
        $result = $policy->manage($user);

        $this->assertTrue($result);
    }

    public function test_member_cannot_manage_members(): void
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

        $policy = new OrganizationMembershipPolicy();
        $result = $policy->manage($user);

        $this->assertFalse($result);
    }

    public function test_view_any_returns_false_without_current_organization(): void
    {
        $user = User::factory()->create();

        $request = $this->createRequestWithoutOrganization();
        $request->setUserResolver(fn () => $user);

        $policy = new OrganizationMembershipPolicy();
        $result = $policy->viewAny($user);

        $this->assertFalse($result);
    }

    public function test_manage_returns_false_without_current_organization(): void
    {
        $user = User::factory()->create();

        $request = $this->createRequestWithoutOrganization();
        $request->setUserResolver(fn () => $user);

        $policy = new OrganizationMembershipPolicy();
        $result = $policy->manage($user);

        $this->assertFalse($result);
    }

    public function test_user_with_role_in_org_a_denied_in_org_b_for_view_any(): void
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

        $policy = new OrganizationMembershipPolicy();
        $result = $policy->viewAny($user);

        $this->assertFalse($result);
    }

    public function test_user_with_role_in_org_a_denied_in_org_b_for_manage(): void
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

        $policy = new OrganizationMembershipPolicy();
        $result = $policy->manage($user);

        $this->assertFalse($result);
    }
}