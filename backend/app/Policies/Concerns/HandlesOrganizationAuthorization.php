<?php

namespace App\Policies\Concerns;

use Illuminate\Http\Request;
use App\Models\Organization;

trait HandlesOrganizationAuthorization
{
    protected function getCurrentOrganization(Request $request): ?Organization
    {
        return $request->attributes->get('current_organization')
            ?? $request->get('current_organization')
            ?? app('current_organization', null);
    }

    protected function hasCurrentOrganization(Request $request): bool
    {
        return $this->getCurrentOrganization($request) !== null;
    }
}