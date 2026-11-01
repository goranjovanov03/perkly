<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Organization;
use App\Policies\Concerns\HandlesOrganizationAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;

class OrganizationPolicy
{
    use HandlesOrganizationAuthorization;

    public function view(Request $request): Response|bool
    {
        if (! $this->hasCurrentOrganization($request)) {
            return false;
        }

        return $request->user()->can('organization.view');
    }

    public function update(Request $request): Response|bool
    {
        if (! $this->hasCurrentOrganization($request)) {
            return false;
        }

        return $request->user()->can('organization.update');
    }
}