<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrganizationController extends Controller
{
    use AuthorizesRequests;
    public function show(Request $request): JsonResponse
    {
        $this->authorize('view', Organization::class);

        $organization = $request->get('current_organization');

        return response()->json([
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $this->authorize('update', Organization::class);

        $organization = $request->get('current_organization');
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|max:255|unique:organizations,slug,' . $organization->id,
        ]);

        $organization->update($request->only(['name', 'slug']));

        return response()->json([
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
                'slug' => $organization->slug,
            ],
        ]);
    }

    public function members(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OrganizationMembership::class);

        $organization = $request->get('current_organization');
        $members = $organization->memberships()->with('user')->get()->map(function ($membership) {
            return [
                'id' => $membership->user->id,
                'name' => $membership->user->name,
                'email' => $membership->user->email,
                'role' => $membership->user->getRoleNames()->first(),
            ];
        });

        return response()->json(['members' => $members]);
    }
}