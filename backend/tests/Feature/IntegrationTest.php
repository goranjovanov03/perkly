<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\IntegrationConnection;
use App\Models\IntegrationSecret;
use App\Models\IntegrationConfigurationVersion;
use App\Models\IntegrationProvider;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;

class IntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->seed(\Database\Seeders\ReferenceDataSeeder::class);
        $this->seed(\Database\Seeders\IntegrationRegistrySeeder::class);
    }

    protected function assignRoleToUserForOrganization(User $user, string $roleName, Organization $organization): void
    {
        $Role = app(config('permission.models.role'));
        $role = $Role::where('name', $roleName)->where('guard_name', 'sanctum')->first();
        $user->roles()->attach($role, ['team_id' => $organization->id]);
    }

    protected function actingAsUserInOrg(User $user, Organization $organization, string $role = 'member'): void
    {
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, $role, $organization);
    }

    public function test_list_integrations_requires_auth(): void
    {
        $response = $this->withHeaders(['X-Organization-Id' => '01HX7Z9K9V9V9V9V9V9V9V9V9V'])
            ->getJson('/api/v1/org/integrations');

        $response->assertStatus(401);
        $response->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_list_integrations_requires_organization_header(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/org/integrations');

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_list_integrations_returns_empty_for_no_connections(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization, 'owner');

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/integrations');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('connections'));
    }

    public function test_list_integrations_returns_connections(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization, 'owner');

        $provider = IntegrationProvider::where('key', 'stripe')->first();
        IntegrationConnection::factory()->create([
            'organization_id' => $organization->id,
            'provider_key' => $provider->key,
            'name' => 'Test Stripe',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson('/api/v1/org/integrations');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('connections'));
        $this->assertEquals('Test Stripe', $response->json('connections.0.name'));
        $this->assertEquals('stripe', $response->json('connections.0.provider_key'));
    }

    public function test_list_integrations_cross_org_403(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organizationA, 'owner');

        $provider = IntegrationProvider::where('key', 'stripe')->first();
        IntegrationConnection::factory()->create([
            'organization_id' => $organizationB->id,
            'provider_key' => $provider->key,
            'name' => 'Org B Stripe',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organizationB->id])
            ->getJson('/api/v1/org/integrations');

        $response->assertStatus(403);
    }

    public function test_create_integration_requires_owner(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization, 'member');

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->postJson('/api/v1/org/integrations', [
                'provider_key' => 'stripe',
                'name' => 'Test Stripe',
                'config' => ['merchant_id' => 'm_123', 'api_key' => 'sk_test_123', 'environment' => 'sandbox'],
            ]);

        $response->assertStatus(403);
    }

    public function test_create_integration_success(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization, 'owner');

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->postJson('/api/v1/org/integrations', [
                'provider_key' => 'stripe',
                'name' => 'Test Stripe',
                'config' => ['merchant_id' => 'm_123', 'api_key' => 'sk_test_123', 'environment' => 'sandbox'],
                'secrets' => ['webhook_secret' => 'whsec_123'],
            ]);

        $response->assertStatus(201);
        $this->assertEquals('Test Stripe', $response->json('connection.name'));
        $this->assertEquals('stripe', $response->json('connection.provider_key'));

        $this->assertDatabaseCount('integration_connections', 1);
        $this->assertDatabaseCount('integration_secrets', 1);
        $this->assertDatabaseCount('integration_configuration_versions', 1);
    }

    public function test_create_integration_validation(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization, 'owner');

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->postJson('/api/v1/org/integrations', [
                'provider_key' => 'stripe',
                'name' => '',
                'config' => [],
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_get_integration_returns_config_and_secret_keys_only(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization, 'owner');

        $provider = IntegrationProvider::where('key', 'stripe')->first();
        $connection = IntegrationConnection::factory()->create([
            'organization_id' => $organization->id,
            'provider_key' => $provider->key,
            'name' => 'Test Stripe',
        ]);

        IntegrationSecret::create([
            'connection_id' => $connection->id,
            'key' => 'api_key',
            'ciphertext' => Crypt::encryptString('sk_test_123'),
        ]);
        IntegrationSecret::create([
            'connection_id' => $connection->id,
            'key' => 'webhook_secret',
            'ciphertext' => Crypt::encryptString('whsec_123'),
        ]);

        IntegrationConfigurationVersion::create([
            'connection_id' => $connection->id,
            'version_number' => 1,
            'config' => ['merchant_id' => 'm_123', 'environment' => 'sandbox'],
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson("/api/v1/org/integrations/{$connection->id}");

        $response->assertStatus(200);
        $this->assertEquals('m_123', $response->json('connection.config.merchant_id'));
        $this->assertEquals('sandbox', $response->json('connection.config.environment'));
        $this->assertEquals(['api_key', 'webhook_secret'], $response->json('connection.secret_keys'));
        $this->assertArrayNotHasKey('api_key', $response->json('connection.config') ?? []);
    }

    public function test_get_integration_cross_org_404(): void
    {
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organizationA, 'owner');

        $provider = IntegrationProvider::where('key', 'stripe')->first();
        $connection = IntegrationConnection::factory()->create([
            'organization_id' => $organizationB->id,
            'provider_key' => $provider->key,
            'name' => 'Org B Stripe',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organizationA->id])
            ->getJson("/api/v1/org/integrations/{$connection->id}");

        $response->assertStatus(404);
    }

    public function test_update_secrets_write_only_returns_key_names(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization, 'owner');

        $provider = IntegrationProvider::where('key', 'stripe')->first();
        $connection = IntegrationConnection::factory()->create([
            'organization_id' => $organization->id,
            'provider_key' => $provider->key,
            'name' => 'Test Stripe',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->putJson("/api/v1/org/integrations/{$connection->id}/secrets", [
                'secrets' => ['api_key' => 'sk_new_123', 'webhook_secret' => 'whsec_new_123'],
            ]);

        $response->assertStatus(200);
        $this->assertEquals(['api_key', 'webhook_secret'], $response->json('secret_keys'));

        $secret = IntegrationSecret::where('connection_id', $connection->id)->where('key', 'api_key')->first();
        $this->assertEquals('sk_new_123', $secret->plaintext);
    }

    public function test_update_secrets_validation(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization, 'owner');

        $provider = IntegrationProvider::where('key', 'stripe')->first();
        $connection = IntegrationConnection::factory()->create([
            'organization_id' => $organization->id,
            'provider_key' => $provider->key,
            'name' => 'Test Stripe',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->putJson("/api/v1/org/integrations/{$connection->id}/secrets", [
                'secrets' => 'not_an_array',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_test_connection_success(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization, 'owner');

        $provider = IntegrationProvider::where('key', 'stripe')->first();
        $connection = IntegrationConnection::factory()->create([
            'organization_id' => $organization->id,
            'provider_key' => $provider->key,
            'name' => 'Test Stripe',
        ]);

        IntegrationSecret::create([
            'connection_id' => $connection->id,
            'key' => 'api_key',
            'ciphertext' => Crypt::encryptString('sk_test_123'),
        ]);

        IntegrationConfigurationVersion::create([
            'connection_id' => $connection->id,
            'version_number' => 1,
            'config' => ['merchant_id' => 'm_123', 'environment' => 'sandbox'],
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->postJson("/api/v1/org/integrations/{$connection->id}/test");

        $response->assertStatus(200);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('Connection test successful', $response->json('message'));
        $this->assertEquals('healthy', $response->json('last_health_status'));

        $connection->refresh();
        $this->assertEquals('healthy', $connection->last_health_status);
        $this->assertNotNull($connection->last_health_at);
    }

    public function test_test_connection_failure_sanitized(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization, 'owner');

        $provider = IntegrationProvider::where('key', 'stripe')->first();
        $connection = IntegrationConnection::factory()->create([
            'organization_id' => $organization->id,
            'provider_key' => $provider->key,
            'name' => 'Test Stripe',
        ]);

        IntegrationConfigurationVersion::create([
            'connection_id' => $connection->id,
            'version_number' => 1,
            'config' => ['merchant_id' => 'm_123', 'environment' => 'sandbox', 'fail' => true],
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->postJson("/api/v1/org/integrations/{$connection->id}/test");

        $response->assertStatus(200);
        $this->assertFalse($response->json('success'));
        $this->assertEquals('Connection test failed', $response->json('message'));
        $this->assertEquals('unhealthy', $response->json('last_health_status'));

        $connection->refresh();
        $this->assertEquals('unhealthy', $connection->last_health_status);
    }

    public function test_update_config_creates_new_version(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization, 'owner');

        $provider = IntegrationProvider::where('key', 'stripe')->first();
        $connection = IntegrationConnection::factory()->create([
            'organization_id' => $organization->id,
            'provider_key' => $provider->key,
            'name' => 'Test Stripe',
        ]);

        IntegrationConfigurationVersion::create([
            'connection_id' => $connection->id,
            'version_number' => 1,
            'config' => ['merchant_id' => 'm_123', 'environment' => 'sandbox'],
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->putJson("/api/v1/org/integrations/{$connection->id}/config", [
                'config' => ['merchant_id' => 'm_456', 'environment' => 'production'],
            ]);

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('version_number'));

        $this->assertDatabaseCount('integration_configuration_versions', 2);
        $versions = IntegrationConfigurationVersion::where('connection_id', $connection->id)->orderBy('version_number')->get();
        $this->assertEquals(1, $versions[0]->version_number);
        $this->assertEquals(2, $versions[1]->version_number);
        $this->assertEquals('m_123', $versions[0]->config['merchant_id']);
        $this->assertEquals('m_456', $versions[1]->config['merchant_id']);
    }

    public function test_audit_log_on_create(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization, 'owner');

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->postJson('/api/v1/org/integrations', [
                'provider_key' => 'stripe',
                'name' => 'Test Stripe',
                'config' => ['merchant_id' => 'm_123', 'api_key' => 'sk_test_123', 'environment' => 'sandbox'],
            ]);

        $audit = \App\Models\AuditLog::where('action', 'integration.created')->first();
        $this->assertNotNull($audit);
        $this->assertEquals($user->id, $audit->actor_id);
        $this->assertEquals('integration_connection', $audit->resource_type);
        $this->assertEquals('stripe', $audit->metadata['provider_key']);
    }

    public function test_audit_log_on_secrets_update(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization, 'owner');

        $provider = IntegrationProvider::where('key', 'stripe')->first();
        $connection = IntegrationConnection::factory()->create([
            'organization_id' => $organization->id,
            'provider_key' => $provider->key,
            'name' => 'Test Stripe',
        ]);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->putJson("/api/v1/org/integrations/{$connection->id}/secrets", [
                'secrets' => ['api_key' => 'sk_new_123'],
            ]);

        $audit = \App\Models\AuditLog::where('action', 'integration.secrets_updated')->first();
        $this->assertNotNull($audit);
        $this->assertEquals($user->id, $audit->actor_id);
        $this->assertEquals('api_key', $audit->metadata['secret_keys'][0]);
        $this->assertArrayNotHasKey('sk_new_123', $audit->metadata);
    }

    public function test_audit_log_on_config_update(): void
    {
        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization, 'owner');

        $provider = IntegrationProvider::where('key', 'stripe')->first();
        $connection = IntegrationConnection::factory()->create([
            'organization_id' => $organization->id,
            'provider_key' => $provider->key,
            'name' => 'Test Stripe',
        ]);

        IntegrationConfigurationVersion::create([
            'connection_id' => $connection->id,
            'version_number' => 1,
            'config' => ['merchant_id' => 'm_123'],
            'created_by' => $user->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->putJson("/api/v1/org/integrations/{$connection->id}/config", [
                'config' => ['merchant_id' => 'm_456'],
            ]);

        $audit = \App\Models\AuditLog::where('action', 'integration.config_updated')->first();
        $this->assertNotNull($audit);
        $this->assertEquals(2, $audit->metadata['version_number']);
    }

    public function test_secrets_never_in_logs(): void
    {
        $handler = new \Monolog\Handler\TestHandler();
        \Illuminate\Support\Facades\Log::swap(
            new \Monolog\Logger('test', [$handler])
        );

        $organization = Organization::factory()->create();
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $this->actingAsUserInOrg($user, $organization, 'owner');

        $provider = IntegrationProvider::where('key', 'stripe')->first();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->postJson('/api/v1/org/integrations', [
                'provider_key' => 'stripe',
                'name' => 'Test Stripe',
                'config' => ['merchant_id' => 'm_123', 'api_key' => 'sk_test_123', 'environment' => 'sandbox'],
                'secrets' => ['api_key' => 'sk_test_123', 'webhook_secret' => 'whsec_123'],
            ]);

        $connection = \App\Models\IntegrationConnection::where('organization_id', $organization->id)
            ->where('name', 'Test Stripe')
            ->first();
        $this->assertNotNull($connection);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->putJson("/api/v1/org/integrations/{$connection->id}/secrets", [
                'secrets' => ['api_key' => 'sk_new_123', 'webhook_secret' => 'whsec_new_123'],
            ]);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->getJson("/api/v1/org/integrations/{$connection->id}");

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->postJson("/api/v1/org/integrations/{$connection->id}/test");

        $connection->update([
            'config' => ['merchant_id' => 'm_123', 'environment' => 'sandbox', 'fail' => true],
        ]);

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->postJson("/api/v1/org/integrations/{$connection->id}/test");

        $records = $handler->getRecords();

        foreach ($records as $record) {
            $message = json_encode($record['message'] ?? '');
            $context = json_encode($record['context'] ?? []);
            $this->assertStringNotContainsString('sk_test_123', $message);
            $this->assertStringNotContainsString('whsec_123', $message);
            $this->assertStringNotContainsString('sk_new_123', $message);
            $this->assertStringNotContainsString('whsec_new_123', $message);
            $this->assertStringNotContainsString('sk_test_123', $context);
            $this->assertStringNotContainsString('whsec_123', $context);
            $this->assertStringNotContainsString('sk_new_123', $context);
            $this->assertStringNotContainsString('whsec_new_123', $context);
        }
    }
}