<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Support\Facades\Hash;

class BootstrapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_bootstrap_returns_user_data(): void
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

        $response = $this->getJson('/api/v1/bootstrap');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'locale', 'onboarded_at'],
            'organizations',
            'current_organization',
            'locales',
            'default_locale',
            'features',
            'capabilities',
        ]);
        $response->assertJsonPath('user.id', $user->id);
        $response->assertJsonPath('user.locale', 'en');
    }

    public function test_bootstrap_returns_organizations(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $org1 = Organization::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'name' => 'Org One',
            'slug' => 'org-one',
        ]);

        $org2 = Organization::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'name' => 'Org Two',
            'slug' => 'org-two',
        ]);

        OrganizationMembership::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'user_id' => $user->id,
            'organization_id' => $org1->id,
            'status' => 'active',
        ]);

        OrganizationMembership::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'user_id' => $user->id,
            'organization_id' => $org2->id,
            'status' => 'active',
        ]);

        $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response = $this->getJson('/api/v1/bootstrap');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('organizations'));
    }

    public function test_bootstrap_without_organization_header(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $org = Organization::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'name' => 'Test Org',
            'slug' => 'test-org',
        ]);

        OrganizationMembership::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'status' => 'active',
        ]);

        $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response = $this->getJson('/api/v1/bootstrap');

        $response->assertStatus(200);
        $this->assertNull($response->json('current_organization'));
        $this->assertEmpty($response->json('capabilities'));
    }

    public function test_bootstrap_with_valid_organization_header(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $org = Organization::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'name' => 'Test Org',
            'slug' => 'test-org',
        ]);

        OrganizationMembership::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'status' => 'active',
        ]);

        $Role = app(config('permission.models.role'));
        $role = $Role::where('name', 'admin')->where('guard_name', 'sanctum')->first();
        $user->roles()->attach($role, ['team_id' => $org->id]);

        $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response = $this->withHeaders([
            'X-Organization-Id' => $org->id,
        ])->getJson('/api/v1/bootstrap');

        $response->assertStatus(200);
        $this->assertNotNull($response->json('current_organization'));
        $this->assertEquals($org->id, $response->json('current_organization.id'));
        $this->assertContains('organization.view', $response->json('capabilities'));
        $this->assertContains('members.view', $response->json('capabilities'));
        $this->assertContains('members.manage', $response->json('capabilities'));
    }

    public function test_bootstrap_with_invalid_organization_header(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $org = Organization::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'name' => 'Test Org',
            'slug' => 'test-org',
        ]);

        OrganizationMembership::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'status' => 'active',
        ]);

        $otherOrg = Organization::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'name' => 'Other Org',
            'slug' => 'other-org',
        ]);

        $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response = $this->withHeaders([
            'X-Organization-Id' => $otherOrg->id,
        ])->getJson('/api/v1/bootstrap');

        $response->assertStatus(200);
        $this->assertNull($response->json('current_organization'));
        $this->assertEmpty($response->json('capabilities'));
    }

    public function test_bootstrap_returns_supported_locales(): void
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

        $response = $this->getJson('/api/v1/bootstrap');

        $response->assertStatus(200);
        $this->assertEquals(['mk', 'en'], $response->json('locales'));
        $this->assertEquals('mk', $response->json('default_locale'));
    }

    public function test_bootstrap_requires_auth(): void
    {
        $response = $this->getJson('/api/v1/bootstrap');

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_bootstrap_features_is_empty_object(): void
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

        $response = $this->getJson('/api/v1/bootstrap');

        $response->assertStatus(200);
        $features = json_decode($response->getContent(), false);
        $this->assertObjectHasProperty('features', $features);
        $this->assertInstanceOf(\stdClass::class, $features->features);
    }
}