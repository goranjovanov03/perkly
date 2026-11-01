<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionNames = [
            'organization.view',
            'organization.update',
            'members.view',
            'members.manage',
            'files.upload',
            'files.view',
            'notifications.view',
            'notifications.preferences',
            'integrations.manage',
        ];

        $Permission = app(config('permission.models.permission'));
        $Role = app(config('permission.models.role'));

        $guards = ['web', 'sanctum'];

        foreach ($guards as $guard) {
            $permissionModels = [];
            foreach ($permissionNames as $permission) {
                $permissionModels[] = $Permission::firstOrCreate(
                    ['name' => $permission, 'guard_name' => $guard],
                    ['id' => (string) Str::ulid()]
                );
            }

            $owner = $Role::firstOrCreate(
                ['name' => 'owner', 'guard_name' => $guard],
                ['id' => (string) Str::ulid()]
            );
            $owner->syncPermissions($permissionModels);

            $admin = $Role::firstOrCreate(
                ['name' => 'admin', 'guard_name' => $guard],
                ['id' => (string) Str::ulid()]
            );
            $adminPermissions = $Permission::whereIn('name', ['organization.view', 'members.view', 'members.manage', 'files.upload', 'files.view', 'notifications.view', 'notifications.preferences', 'integrations.manage'])->where('guard_name', $guard)->get();
            $admin->syncPermissions($adminPermissions);

            $member = $Role::firstOrCreate(
                ['name' => 'member', 'guard_name' => $guard],
                ['id' => (string) Str::ulid()]
            );
            $memberPermissions = $Permission::whereIn('name', ['organization.view', 'members.view', 'files.view', 'notifications.view', 'notifications.preferences'])->where('guard_name', $guard)->get();
            $member->syncPermissions($memberPermissions);
        }
    }
}