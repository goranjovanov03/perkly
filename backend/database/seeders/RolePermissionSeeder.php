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

        $permissionIds = [];
        foreach ($permissionNames as $permission) {
            $perm = $Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web'],
                ['id' => (string) Str::ulid()]
            );
            dump('Created permission', $permission, 'id', $perm->id, 'type', gettype($perm->id));
            $permissionIds[] = $perm->id;
        }

        $owner = $Role::firstOrCreate(
            ['name' => 'owner', 'guard_name' => 'web'],
            ['id' => (string) Str::ulid()]
        );
        dump('Created owner role', 'id', $owner->id, 'type', gettype($owner->id));

        $admin = $Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'web'],
            ['id' => (string) Str::ulid()]
        );
        dump('Created admin role', 'id', $admin->id, 'type', gettype($admin->id));

        $member = $Role::firstOrCreate(
            ['name' => 'member', 'guard_name' => 'web'],
            ['id' => (string) Str::ulid()]
        );
        dump('Created member role', 'id', $member->id, 'type', gettype($member->id));

        // Use raw DB to avoid any ID casting issues
        dump('Permission IDs', $permissionIds);
        dump('Owner role ID', $owner->id);
        foreach ($permissionIds as $permId) {
            dump('Inserting', ['permission_id' => $permId, 'role_id' => $owner->id]);
            DB::table('role_has_permissions')->insert([
                'permission_id' => $permId,
                'role_id' => $owner->id,
            ]);
        }

        $adminPerms = $Permission::whereIn('name', ['organization.view', 'members.view', 'members.manage'])->where('guard_name', 'web')->pluck('id')->toArray();
        dump('Admin perms', $adminPerms);
        foreach ($adminPerms as $permId) {
            dump('Inserting admin', ['permission_id' => $permId, 'role_id' => $admin->id]);
            DB::table('role_has_permissions')->insert([
                'permission_id' => $permId,
                'role_id' => $admin->id,
            ]);
        }

        $memberPerms = $Permission::whereIn('name', ['organization.view', 'members.view'])->where('guard_name', 'web')->pluck('id')->toArray();
        dump('Member perms', $memberPerms);
        foreach ($memberPerms as $permId) {
            dump('Inserting member', ['permission_id' => $permId, 'role_id' => $member->id]);
            DB::table('role_has_permissions')->insert([
                'permission_id' => $permId,
                'role_id' => $member->id,
            ]);
        }
    }
}