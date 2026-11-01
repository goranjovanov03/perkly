<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FileTest extends TestCase
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

    protected function createTestFile(string $mimeType = 'application/pdf'): UploadedFile
    {
        return UploadedFile::fake()->create('test.' . $this->getExtension($mimeType), 5, $mimeType);
    }

    protected function createOversizeFile(): UploadedFile
    {
        return UploadedFile::fake()->create('oversize.pdf', 11 * 1024, 'application/pdf');
    }

    private function getExtension(string $mimeType): string
    {
        return match ($mimeType) {
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            default => 'bin',
        };
    }

    public function test_upload_pdf_ok(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'owner', $organization);

        $testFile = $this->createTestFile('application/pdf');
        $expectedSize = $testFile->getSize();

        $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->post('/api/v1/org/files', [
                'file' => $testFile,
            ])
            ->assertStatus(201)
            ->assertJsonStructure([
                'file' => ['id', 'original_name', 'mime_type', 'size_bytes', 'sha256_hash', 'created_at'],
            ])
            ->assertJsonPath('file.mime_type', 'application/pdf');

        $file = File::where('organization_id', $organization->id)->first();
        $this->assertNotNull($file);
        $this->assertEquals('application/pdf', $file->mime_type);
        $this->assertEquals($expectedSize, $file->size_bytes);

        $auditLog = \App\Models\AuditLog::where('action', 'file.uploaded')->first();
        $this->assertNotNull($auditLog);
        $this->assertEquals($user->id, $auditLog->actor_id);
        $this->assertEquals($organization->id, $auditLog->organization_id);
        $this->assertEquals($file->id, $auditLog->resource_id);
    }

    public function test_upload_png_ok(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'owner', $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->post('/api/v1/org/files', [
                'file' => $this->createTestFile('image/png'),
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('file.mime_type', 'image/png');
    }

    public function test_upload_jpg_ok(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'owner', $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->post('/api/v1/org/files', [
                'file' => $this->createTestFile('image/jpeg'),
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('file.mime_type', 'image/jpeg');
    }

    public function test_upload_webp_ok(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'owner', $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->post('/api/v1/org/files', [
                'file' => $this->createTestFile('image/webp'),
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('file.mime_type', 'image/webp');
    }

    public function test_upload_wrong_mime_type_rejected(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'owner', $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->post('/api/v1/org/files', [
                'file' => $this->createTestFile('application/x-executable'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $response->assertJsonPath('error.fields.file.0', 'The file type is not allowed. Allowed types: PDF, PNG, JPG, WebP.');
    }

    public function test_upload_oversize_rejected(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'owner', $organization);

        $oversizeFile = $this->createOversizeFile();

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->post('/api/v1/org/files', [
                'file' => $oversizeFile,
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $response->assertJsonPath('error.fields.file.0', 'The file field must not be greater than 10240 kilobytes.');
    }

    public function test_member_without_files_upload_gets_403(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'member', $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->post('/api/v1/org/files', [
                'file' => $this->createTestFile('application/pdf'),
            ]);

        $response->assertStatus(403);
        $response->assertJsonPath('error.code', 'FORBIDDEN');
    }

    public function test_admin_can_upload(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'admin', $organization);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->post('/api/v1/org/files', [
                'file' => $this->createTestFile('application/pdf'),
            ]);

        $response->assertStatus(201);
    }

    public function test_list_files(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'member', $organization);

        File::factory()->count(3)->create([
            'organization_id' => $organization->id,
            'uploader_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->get('/api/v1/org/files');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('files'));
    }

    public function test_list_files_scoped_to_organization(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organizationA->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'member', $organizationA);

        File::factory()->create([
            'organization_id' => $organizationA->id,
            'uploader_id' => $user->id,
        ]);
        File::factory()->create([
            'organization_id' => $organizationB->id,
            'uploader_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organizationA->id])
            ->get('/api/v1/org/files');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('files'));
    }

    public function test_show_file_metadata_with_download_url(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'member', $organization);

        $file = File::factory()->create([
            'organization_id' => $organization->id,
            'uploader_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->get("/api/v1/org/files/{$file->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'file' => ['id', 'original_name', 'mime_type', 'size_bytes', 'sha256_hash', 'created_at', 'uploader', 'download_url'],
        ]);
        $this->assertStringContainsString('api/files/download', $response->json('file.download_url'));
    }

    public function test_show_file_cross_tenant_returns_404(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $organizationA = Organization::factory()->create();
        $organizationB = Organization::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organizationA->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'member', $organizationA);

        $file = File::factory()->create([
            'organization_id' => $organizationB->id,
            'uploader_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organizationA->id])
            ->get("/api/v1/org/files/{$file->id}");

        $response->assertStatus(404);
    }

    public function test_download_file_with_valid_signature(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'member', $organization);

        $file = File::factory()->create([
            'organization_id' => $organization->id,
            'uploader_id' => $user->id,
            'stored_path' => 'organizations/' . $organization->id . '/' . Str::ulid() . '.pdf',
        ]);

        Storage::disk('private')->put($file->stored_path, 'file content');

        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'files.download',
            now()->addMinutes(5),
            ['file' => $file->id]
        );

        $response = $this->actingAs($user, 'sanctum')->get($url);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $response->assertHeader('Content-Disposition', 'attachment; filename="' . $file->original_name . '"');
    }

    public function test_download_file_expired_signature_returns_403(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'member', $organization);

        $file = File::factory()->create([
            'organization_id' => $organization->id,
            'uploader_id' => $user->id,
        ]);

        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'files.download',
            now()->subMinutes(1),
            ['file' => $file->id]
        );

        $response = $this->actingAs($user, 'sanctum')->get($url);

        $response->assertStatus(403);
    }

    public function test_download_file_tampered_signature_returns_403(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'member', $organization);

        $file = File::factory()->create([
            'organization_id' => $organization->id,
            'uploader_id' => $user->id,
        ]);

        $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'files.download',
            now()->addMinutes(5),
            ['file' => $file->id]
        );

        $tamperedUrl = $url . '&tampered=1';

        $response = $this->actingAs($user, 'sanctum')->get($tamperedUrl);

        $response->assertStatus(403);
    }

    public function test_no_stored_path_in_api_response(): void
    {
        Storage::fake('private');

        $user = User::factory()->create(['password' => Hash::make('password123')]);
        $organization = Organization::factory()->create();
        OrganizationMembership::factory()->create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => 'active',
        ]);
        $this->assignRoleToUserForOrganization($user, 'owner', $organization);

        $testFile = $this->createTestFile('application/pdf');

        $response = $this->actingAs($user, 'sanctum')
            ->withHeaders(['X-Organization-Id' => $organization->id])
            ->post('/api/v1/org/files', [
                'file' => $testFile,
            ]);

        $response->assertStatus(201);
        $json = $response->json();
        $this->assertArrayNotHasKey('stored_path', $json['file']);
        $this->assertArrayNotHasKey('stored_path', $json);
    }
}