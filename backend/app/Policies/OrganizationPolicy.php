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

    public function view(User $user): Response|bool
    {
        $request = request();

        if (! $this->hasCurrentOrganization($request)) {
            return false;
        }

        return $user->can('organization.view', 'sanctum');
    }

    public function update(User $user): Response|bool
    {
        $request = request();

        if (! $this->hasCurrentOrganization($request)) {
            return false;
        }

        return $user->can('organization.update', 'sanctum');
    }
}