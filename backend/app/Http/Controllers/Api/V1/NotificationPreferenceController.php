<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Models\Organization;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class NotificationPreferenceController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $this->authorize('notifications.preferences', Organization::class);

        $organization = $request->get('current_organization');
        $user = $request->user();

        $preferences = NotificationPreference::where('user_id', $user->id)
            ->where(function ($q) use ($organization) {
                $q->where('organization_id', $organization->id)
                  ->orWhereNull('organization_id');
            })
            ->get()
            ->groupBy('channel')
            ->map(function ($channelPrefs) {
                return $channelPrefs->mapWithKeys(function ($pref) {
                    return [$pref->type => $pref->enabled];
                });
            })
            ->toArray();

        return response()->json(['preferences' => $preferences]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorize('notifications.preferences', Organization::class);

        $organization = $request->get('current_organization');
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'preferences' => 'required|array',
            'preferences.*.channel' => 'required|string|in:in_app,push,email',
            'preferences.*.type' => 'required|string',
            'preferences.*.enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Validation failed.',
                    'request_id' => $request->header('X-Request-Id') ?? (string) \Illuminate\Support\Str::uuid(),
                    'fields' => $validator->errors()->toArray(),
                ],
            ], 422);
        }

        foreach ($request->preferences as $pref) {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'organization_id' => $organization->id,
                    'channel' => $pref['channel'],
                    'type' => $pref['type'],
                ],
                ['enabled' => $pref['enabled']]
            );
        }

        return $this->index($request);
    }
}