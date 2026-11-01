<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;

class AuditLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_login_creates_audit_log(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        $auditLog = AuditLog::where('action', 'auth.login')->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals($user->id, $auditLog->actor_id);
        $this->assertEquals('user', $auditLog->resource_type);
        $this->assertEquals($user->id, $auditLog->resource_id);
        $this->assertNull($auditLog->organization_id);
        $this->assertNotNull($auditLog->request_id);
        $this->assertEmpty($auditLog->metadata);
    }

    public function test_failed_login_creates_audit_log(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);

        $auditLog = AuditLog::where('action', 'auth.login_failed')->first();
        $this->assertNotNull($auditLog);
        $this->assertNull($auditLog->actor_id);
        $this->assertNull($auditLog->resource_type);
        $this->assertNull($auditLog->resource_id);
        $this->assertArrayHasKey('email', $auditLog->metadata);
        $this->assertEquals($user->email, $auditLog->metadata['email']);
    }

    public function test_logout_creates_audit_log(): void
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

        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(200);

        $auditLog = AuditLog::where('action', 'auth.logout')->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals($user->id, $auditLog->actor_id);
        $this->assertEquals('user', $auditLog->resource_type);
        $this->assertEquals($user->id, $auditLog->resource_id);
    }

    public function test_password_change_creates_audit_log(): void
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

        $response = $this->patchJson('/api/v1/me/password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200);

        $auditLog = AuditLog::where('action', 'auth.password_changed')->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals($user->id, $auditLog->actor_id);
        $this->assertEquals('user', $auditLog->resource_type);
        $this->assertEquals($user->id, $auditLog->resource_id);
    }

    public function test_failed_password_change_creates_audit_log(): void
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

        $response = $this->patchJson('/api/v1/me/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);

        $auditLog = AuditLog::where('action', 'auth.password_change_failed')->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals($user->id, $auditLog->actor_id);
        $this->assertEquals('user', $auditLog->resource_type);
        $this->assertEquals($user->id, $auditLog->resource_id);
    }

    public function test_organization_update_creates_audit_log(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $organization = Organization::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'name' => 'Test Org',
            'slug' => 'test-org',
        ]);

        OrganizationMembership::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);

        $Role = app(config('permission.models.role'));
        $role = $Role::where('name', 'owner')->where('guard_name', 'sanctum')->first();
        $user->roles()->attach($role, ['team_id' => $organization->id]);

        $this->withHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
        ])->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response = $this->withHeaders([
            'X-Organization-Id' => $organization->id,
        ])->patchJson('/api/v1/org/current', [
            'name' => 'Updated Org',
        ]);

        $response->assertStatus(200);

        $auditLog = AuditLog::where('action', 'organization.updated')->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals($user->id, $auditLog->actor_id);
        $this->assertEquals($organization->id, $auditLog->organization_id);
        $this->assertEquals('organization', $auditLog->resource_type);
        $this->assertEquals($organization->id, $auditLog->resource_id);
        $this->assertArrayHasKey('old', $auditLog->metadata);
        $this->assertArrayHasKey('new', $auditLog->metadata);
        $this->assertEquals('Test Org', $auditLog->metadata['old']['name']);
        $this->assertEquals('Updated Org', $auditLog->metadata['new']['name']);
    }

    public function test_audit_log_metadata_redacts_passwords(): void
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

        $auditLog = AuditLog::where('action', 'auth.login')->first();
        $this->assertNotNull($auditLog);
        $this->assertEmpty($auditLog->metadata);
    }
}