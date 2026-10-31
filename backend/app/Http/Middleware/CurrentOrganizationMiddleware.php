<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Organization;
use App\Models\OrganizationMembership;

class CurrentOrganizationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $organizationId = $request->header('X-Organization-Id');

        if (! $organizationId) {
            return $this->forbidden($request);
        }

        $organization = Organization::find($organizationId);

        if (! $organization) {
            return $this->forbidden($request);
        }

        $user = $request->user();

        if (! $user) {
            return $this->forbidden($request);
        }

        $membership = OrganizationMembership::where('user_id', $user->id)
            ->where('organization_id', $organization->id)
            ->where('status', 'active')
            ->first();

        if (! $membership) {
            return $this->forbidden($request);
        }

        $request->merge(['current_organization' => $organization]);
        $request->attributes->set('current_organization', $organization);
        app()->instance('current_organization', $organization);

        return $next($request);
    }

    private function forbidden(Request $request): Response
    {
        $requestId = $request->header('X-Request-Id') ?? (string) \Illuminate\Support\Str::uuid();

        return response()->json([
            'error' => [
                'code' => 'FORBIDDEN',
                'message' => 'Forbidden.',
                'request_id' => $requestId,
            ],
        ], 403);
    }
}