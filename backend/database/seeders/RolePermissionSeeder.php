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
        ];

        $Permission = app(config('permission.models.permission'));
        $Role = app(config('permission.models.role'));

        $guards = ['web', 'sanctum'];

        $permissionIds = [];
        foreach ($permissionNames as $permission) {
            foreach ($guards as $guard) {
                $perm = $Permission::firstOrCreate(
                    ['name' => $permission, 'guard_name' => $guard],
                    ['id' => (string) Str::ulid()]
                );
                $permissionIds[$guard][] = $perm->id;
            }
        }

        foreach ($guards as $guard) {
            $owner = $Role::firstOrCreate(
                ['name' => 'owner', 'guard_name' => $guard],
                ['id' => (string) Str::ulid()]
            );

            $admin = $Role::firstOrCreate(
                ['name' => 'admin', 'guard_name' => $guard],
                ['id' => (string) Str::ulid()]
            );

            $member = $Role::firstOrCreate(
                ['name' => 'member', 'guard_name' => $guard],
                ['id' => (string) Str::ulid()]
            );

            // Use raw DB to avoid any ID casting issues
            foreach ($permissionIds[$guard] as $permId) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permId,
                    'role_id' => $owner->id,
                ]);
            }

            $adminPerms = $Permission::whereIn('name', ['organization.view', 'members.view', 'members.manage'])->where('guard_name', $guard)->pluck('id')->toArray();
            foreach ($adminPerms as $permId) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permId,
                    'role_id' => $admin->id,
                ]);
            }

            $memberPerms = $Permission::whereIn('name', ['organization.view', 'members.view'])->where('guard_name', $guard)->pluck('id')->toArray();
            foreach ($memberPerms as $permId) {
                DB::table('role_has_permissions')->insert([
                    'permission_id' => $permId,
                    'role_id' => $member->id,
                ]);
            }
        }
    }
}