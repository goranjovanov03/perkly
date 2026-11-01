<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\IntegrationConnection;
use App\Models\IntegrationSecret;
use App\Models\IntegrationConfigurationVersion;
use App\Models\IntegrationProvider;
use App\Models\Organization;
use App\Services\Integrations\IntegrationTestProvider;
use App\Services\Integrations\FakeIntegrationTestProvider;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class IntegrationController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('integrations.manage', Organization::class);

        $organization = $request->get('current_organization');

        $connections = IntegrationConnection::where('organization_id', $organization->id)
            ->with('provider.serviceType')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($connection) {
                return [
                    'id' => $connection->id,
                    'provider_key' => $connection->provider_key,
                    'provider_name' => $connection->provider->name ?? null,
                    'service_type' => $connection->provider->serviceType->key ?? null,
                    'service_type_name' => $connection->provider->serviceType->name ?? null,
                    'name' => $connection->name,
                    'status' => $connection->status,
                    'last_health_status' => $connection->last_health_status,
                    'last_health_at' => $connection->last_health_at,
                    'created_at' => $connection->created_at,
                    'updated_at' => $connection->updated_at,
                ];
            });

        return response()->json(['connections' => $connections]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('integrations.manage', Organization::class);

        $organization = $request->get('current_organization');

        $validator = Validator::make($request->all(), [
            'provider_key' => 'required|string|exists:integration_providers,key',
            'name' => 'required|string|max:255',
            'config' => 'required|array',
            'secrets' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed.',
                    'request_id' => $request->header('X-Request-Id') ?? Str::uuid(),
                    'fields' => $validator->errors()->toArray(),
                ],
            ], 422);
        }

        $provider = IntegrationProvider::where('key', $request->provider_key)->firstOrFail();

        $connection = IntegrationConnection::create([
            'id' => Str::ulid(),
            'organization_id' => $organization->id,
            'provider_key' => $provider->key,
            'name' => $request->name,
            'status' => 'active',
        ]);

        if (! empty($request->secrets)) {
            foreach ($request->secrets as $key => $value) {
                IntegrationSecret::create([
                    'connection_id' => $connection->id,
                    'key' => $key,
                    'ciphertext' => Crypt::encryptString($value),
                ]);
            }
        }

        IntegrationConfigurationVersion::create([
            'connection_id' => $connection->id,
            'version_number' => 1,
            'config' => $request->config,
            'created_by' => $request->user()->id,
        ]);

        audit('integration.created', 'integration_connection', $connection->id, [
            'provider_key' => $provider->key,
            'name' => $connection->name,
        ]);

        return response()->json([
            'connection' => [
                'id' => $connection->id,
                'provider_key' => $connection->provider_key,
                'name' => $connection->name,
                'status' => $connection->status,
                'created_at' => $connection->created_at,
            ],
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $this->authorize('integrations.manage', Organization::class);

        $organization = $request->get('current_organization');

        $connection = IntegrationConnection::where('organization_id', $organization->id)
            ->where('id', $id)
            ->with('provider.serviceType')
            ->firstOrFail();

        $latestConfig = $connection->configurationVersions()->latest('version_number')->first();

        $secretKeys = $connection->secrets->pluck('key')->toArray();

        return response()->json([
            'connection' => [
                'id' => $connection->id,
                'provider_key' => $connection->provider_key,
                'provider_name' => $connection->provider->name ?? null,
                'service_type' => $connection->provider->serviceType->key ?? null,
                'service_type_name' => $connection->provider->serviceType->name ?? null,
                'name' => $connection->name,
                'status' => $connection->status,
                'config' => $latestConfig?->config ?? [],
                'secret_keys' => $secretKeys,
                'last_health_status' => $connection->last_health_status,
                'last_health_at' => $connection->last_health_at,
                'created_at' => $connection->created_at,
                'updated_at' => $connection->updated_at,
            ],
        ]);
    }

    public function updateSecrets(Request $request, string $id): JsonResponse
    {
        $this->authorize('integrations.manage', Organization::class);

        $organization = $request->get('current_organization');

        $connection = IntegrationConnection::where('organization_id', $organization->id)
            ->where('id', $id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'secrets' => 'required|array',
            'secrets.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed.',
                    'request_id' => $request->header('X-Request-Id') ?? Str::uuid(),
                    'fields' => $validator->errors()->toArray(),
                ],
            ], 422);
        }

        foreach ($request->secrets as $key => $value) {
            IntegrationSecret::updateOrCreate(
                ['connection_id' => $connection->id, 'key' => $key],
                ['ciphertext' => Crypt::encryptString($value)]
            );
        }

        audit('integration.secrets_updated', 'integration_connection', $connection->id, [
            'secret_keys' => array_keys($request->secrets),
        ]);

        $secretKeys = $connection->secrets->pluck('key')->toArray();

        return response()->json([
            'connection_id' => $connection->id,
            'secret_keys' => $secretKeys,
        ]);
    }

    public function test(Request $request, string $id): JsonResponse
    {
        $this->authorize('integrations.manage', Organization::class);

        $organization = $request->get('current_organization');

        $connection = IntegrationConnection::where('organization_id', $organization->id)
            ->where('id', $id)
            ->with('provider', 'secrets')
            ->firstOrFail();

        $latestConfig = $connection->configurationVersions()->latest('version_number')->first();
        $config = $latestConfig?->config ?? [];

        $secrets = [];
        foreach ($connection->secrets as $secret) {
            $secrets[$secret->key] = $secret->plaintext;
        }

        $provider = app()->make(IntegrationTestProvider::class);
        $result = $provider->test($config, $secrets);

        $connection->update([
            'last_health_status' => $result['success'] ? 'healthy' : 'unhealthy',
            'last_health_at' => now(),
        ]);

        audit('integration.tested', 'integration_connection', $connection->id, [
            'success' => $result['success'],
            'message' => $result['message'],
        ]);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'last_health_status' => $result['success'] ? 'healthy' : 'unhealthy',
            'last_health_at' => now()->toIso8601String(),
        ]);
    }

    public function updateConfig(Request $request, string $id): JsonResponse
    {
        $this->authorize('integrations.manage', Organization::class);

        $organization = $request->get('current_organization');

        $connection = IntegrationConnection::where('organization_id', $organization->id)
            ->where('id', $id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'config' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed.',
                    'request_id' => $request->header('X-Request-Id') ?? Str::uuid(),
                    'fields' => $validator->errors()->toArray(),
                ],
            ], 422);
        }

        $latestVersion = $connection->configurationVersions()->max('version_number') ?? 0;

        IntegrationConfigurationVersion::create([
            'connection_id' => $connection->id,
            'version_number' => $latestVersion + 1,
            'config' => $request->config,
            'created_by' => $request->user()->id,
        ]);

        audit('integration.config_updated', 'integration_connection', $connection->id, [
            'version_number' => $latestVersion + 1,
        ]);

        return response()->json([
            'connection_id' => $connection->id,
            'version_number' => $latestVersion + 1,
        ]);
    }
}