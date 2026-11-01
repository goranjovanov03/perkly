<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\HandlesOrganizationAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Http\Request;

class OrganizationMembershipPolicy
{
    use HandlesOrganizationAuthorization;

    public function viewAny(User $user): Response|bool
    {
        $request = request();

        if (! $this->hasCurrentOrganization($request)) {
            return false;
        }

        return $user->can('members.view', 'sanctum');
    }

    public function manage(User $user): Response|bool
    {
        $request = request();

        if (! $this->hasCurrentOrganization($request)) {
            return false;
        }

        return $user->can('members.manage', 'sanctum');
    }
}