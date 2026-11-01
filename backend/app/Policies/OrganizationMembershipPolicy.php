<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\HandlesOrganizationAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;

class OrganizationMembershipPolicy
{
    use HandlesOrganizationAuthorization;

    public function viewAny(Request $request): Response|bool
    {
        if (! $this->hasCurrentOrganization($request)) {
            return false;
        }

        return $request->user()->can('members.view');
    }

    public function manage(Request $request): Response|bool
    {
        if (! $this->hasCurrentOrganization($request)) {
            return false;
        }

        return $request->user()->can('members.manage');
    }
}