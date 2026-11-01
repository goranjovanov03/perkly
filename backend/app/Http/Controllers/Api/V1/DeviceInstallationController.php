<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Enums\DevicePlatform;
use App\Models\DeviceInstallation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DeviceInstallationController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'required|string|in:android,ios',
            'push_token' => 'required|string|max:500',
            'app_version' => 'nullable|string|max:50',
            'locale' => 'nullable|string|in:mk,en',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed.',
                    'request_id' => $request->header('X-Request-Id') ?? (string) Str::uuid(),
                    'fields' => $validator->errors()->toArray(),
                ],
            ], 422);
        }

        $user = $request->user();

        $installation = DeviceInstallation::updateOrCreate(
            [
                'user_id' => $user->id,
                'push_token' => $request->push_token,
            ],
            [
                'id' => Str::ulid(),
                'platform' => $request->platform,
                'app_version' => $request->app_version,
                'locale' => $request->locale ?? $user->locale ?? 'mk',
                'revoked_at' => null,
                'last_seen_at' => now(),
            ]
        );

        return response()->json([
            'installation' => [
                'id' => $installation->id,
                'platform' => $installation->platform,
                'push_token' => $installation->push_token,
                'app_version' => $installation->app_version,
                'locale' => $installation->locale,
                'last_seen_at' => $installation->last_seen_at,
                'revoked_at' => $installation->revoked_at,
                'created_at' => $installation->created_at,
            ],
        ], 201);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $installation = DeviceInstallation::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $installation->revoke();

        return response()->json(['message' => 'Device installation revoked']);
    }
}