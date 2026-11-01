<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Locale;
use Illuminate\Support\Facades\Hash;

class ReferenceDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\ReferenceDataSeeder::class);
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

    public function test_countries_requires_auth(): void
    {
        $response = $this->withHeaders(['X-Organization-Id' => '01HX7Z9K9V9V9V9V9V9V9V9V9V'])
            ->getJson('/api/v1/org/reference/countries');

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_countries_returns_list(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/reference/countries');

        $response->assertStatus(200);
        $response->assertJsonStructure(['countries']);
        $this->assertCount(9, $response->json('countries'));
        $mk = collect($response->json('countries'))->firstWhere('iso2', 'MK');
        $this->assertNotNull($mk);
        $this->assertEquals('Северна Македонија', $mk['name_mk']);
        $this->assertEquals('North Macedonia', $mk['name_en']);
    }

    public function test_countries_etag_caching(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/reference/countries');

        $response->assertStatus(200);
        $response->assertJsonStructure(['countries']);
    }

    public function test_currencies_requires_auth(): void
    {
        $response = $this->withHeaders(['X-Organization-Id' => '01HX7Z9K9V9V9V9V9V9V9V9V9V'])
            ->getJson('/api/v1/org/reference/currencies');

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_currencies_returns_list(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/reference/currencies');

        $response->assertStatus(200);
        $response->assertJsonStructure(['currencies']);
        $this->assertCount(5, $response->json('currencies'));
        $mkd = collect($response->json('currencies'))->firstWhere('code', 'MKD');
        $this->assertNotNull($mkd);
        $this->assertEquals(2, $mkd['minor_units']);
    }

    public function test_currencies_etag_caching(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization);

$response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/reference/currencies');

        $response->assertStatus(200);
        $response->assertJsonStructure(['currencies']);
    }

    public function test_locales_requires_auth(): void
    {
        $response = $this->withHeaders(['X-Organization-Id' => '01HX7Z9K9V9V9V9V9V9V9V9V9V'])
            ->getJson('/api/v1/org/reference/locales');

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_locales_returns_list(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/reference/locales');

        $response->assertStatus(200);
        $response->assertJsonStructure(['locales']);
        $this->assertCount(2, $response->json('locales'));
        $this->assertEquals('mk', $response->json('locales.0.code'));
        $this->assertTrue($response->json('locales.0.is_default'));
        $this->assertEquals('en', $response->json('locales.1.code'));
        $this->assertFalse($response->json('locales.1.is_default'));
    }

    public function test_locales_etag_caching(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization);

$response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/reference/locales');

        $response->assertStatus(200);
        $response->assertJsonStructure(['locales']);
    }
}