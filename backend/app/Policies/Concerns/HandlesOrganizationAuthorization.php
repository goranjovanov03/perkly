<?php

namespace App\Policies\Concerns;

use Illuminate\Http\Request;
use App\Models\Organization;

trait HandlesOrganizationAuthorization
{
    protected function getCurrentOrganization(Request $request): ?Organization
    {
        $org = $request->attributes->get('current_organization')
            ?? $request->get('current_organization');

        if ($org instanceof Organization) {
            return $org;
        }

        if (app()->bound('current_organization')) {
            $org = app('current_organization');
            if ($org instanceof Organization) {
                return $org;
            }
        }

        return null;
    }

    protected function hasCurrentOrganization(Request $request): bool
    {
        return $this->getCurrentOrganization($request) !== null;
    }
}