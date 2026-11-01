<?php

namespace App\Providers;

use App\Models\File;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Policies\FilePolicy;
use App\Policies\OrganizationMembershipPolicy;
use App\Policies\OrganizationPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        File::class => FilePolicy::class,
        Organization::class => OrganizationPolicy::class,
        OrganizationMembership::class => OrganizationMembershipPolicy::class,
    ];

    public function boot(): void
    {
        parent::boot();
    }
}