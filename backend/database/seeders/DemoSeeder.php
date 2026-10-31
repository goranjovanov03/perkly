<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Person;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Permission\Role;
use Spatie\Permission\PermissionRegistrar;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);

        $user = User::firstOrCreate(
            ['email' => 'demo@perkly.test'],
            [
                'id' => (string) Str::ulid(),
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $person = Person::firstOrCreate(
            ['email' => 'demo@perkly.test'],
            [
                'id' => (string) Str::ulid(),
                'first_name' => 'Demo',
                'last_name' => 'User',
                'phone' => '+1234567890',
                'birth_date' => '1990-01-01',
            ]
        );

        $user->person_id = $person->id;
        $user->save();

        $org1 = Organization::firstOrCreate(
            ['slug' => 'demo-org-1'],
            [
                'id' => (string) Str::ulid(),
                'name' => 'Demo Organization 1',
            ]
        );

        $org2 = Organization::firstOrCreate(
            ['slug' => 'demo-org-2'],
            [
                'id' => (string) Str::ulid(),
                'name' => 'Demo Organization 2',
            ]
        );

        OrganizationMembership::firstOrCreate(
            ['user_id' => $user->id, 'organization_id' => $org1->id],
            [
                'id' => (string) Str::ulid(),
                'status' => 'active',
            ]
        );

        OrganizationMembership::firstOrCreate(
            ['user_id' => $user->id, 'organization_id' => $org2->id],
            [
                'id' => (string) Str::ulid(),
                'status' => 'active',
            ]
        );

        $Role = app(config('permission.models.role'));

        app(PermissionRegistrar::class)->setPermissionsTeamId($org1->id);
        $ownerRole = $Role::where('name', 'owner')->where('guard_name', 'web')->first();
        $user->roles()->attach($ownerRole, ['team_id' => $org1->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($org2->id);
        $memberRole = $Role::where('name', 'member')->where('guard_name', 'web')->first();
        $user->roles()->attach($memberRole, ['team_id' => $org2->id]);

        app(PermissionRegistrar::class)->setPermissionsTeamId(null);
    }
}