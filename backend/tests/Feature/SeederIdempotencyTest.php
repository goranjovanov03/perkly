<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Person;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\Permission\Role;
use App\Models\Permission\Permission;

class SeederIdempotencyTest extends TestCase
{
    protected function countTables(): array
    {
        return [
            'users' => DB::table('users')->count(),
            'people' => DB::table('people')->count(),
            'organizations' => DB::table('organizations')->count(),
            'organization_memberships' => DB::table('organization_memberships')->count(),
            'roles' => DB::table('roles')->count(),
            'permissions' => DB::table('permissions')->count(),
            'role_has_permissions' => DB::table('role_has_permissions')->count(),
            'model_has_roles' => DB::table('model_has_roles')->count(),
        ];
    }

    protected function assertCountsUnchanged(array $before, array $after): void
    {
        foreach ($before as $table => $count) {
            $this->assertEquals($count, $after[$table], "Row count changed for table {$table}");
        }
    }

    public function test_database_seeder_twice(): void
    {
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
        $counts1 = $this->countTables();

        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
        $counts2 = $this->countTables();

        $this->assertCountsUnchanged($counts1, $counts2);
    }

    public function test_demo_seeder_twice(): void
    {
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
        $this->artisan('db:seed', ['--class' => 'DemoSeeder']);
        $counts1 = $this->countTables();

        $this->artisan('db:seed', ['--class' => 'DemoSeeder']);
        $counts2 = $this->countTables();

        $this->assertCountsUnchanged($counts1, $counts2);
    }

    public function test_database_and_demo_seeder_twice_in_row(): void
    {
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
        $this->artisan('db:seed', ['--class' => 'DemoSeeder']);
        $counts1 = $this->countTables();

        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
        $this->artisan('db:seed', ['--class' => 'DemoSeeder']);
        $counts2 = $this->countTables();

        $this->assertCountsUnchanged($counts1, $counts2);
    }

    public function test_role_permissions_unchanged_after_rerun(): void
    {
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
        $ownerRoleCount1 = DB::table('role_has_permissions')
            ->join('roles', 'role_has_permissions.role_id', '=', 'roles.id')
            ->where('roles.name', 'owner')
            ->where('roles.guard_name', 'web')
            ->count();

        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
        $ownerRoleCount2 = DB::table('role_has_permissions')
            ->join('roles', 'role_has_permissions.role_id', '=', 'roles.id')
            ->where('roles.name', 'owner')
            ->where('roles.guard_name', 'web')
            ->count();

        $this->assertEquals($ownerRoleCount1, $ownerRoleCount2);
    }

    public function test_demo_user_roles_not_duplicated(): void
    {
        $this->artisan('db:seed', ['--class' => 'DatabaseSeeder']);
        $this->artisan('db:seed', ['--class' => 'DemoSeeder']);

        $modelHasRoles1 = DB::table('model_has_roles')->count();

        $this->artisan('db:seed', ['--class' => 'DemoSeeder']);
        $modelHasRoles2 = DB::table('model_has_roles')->count();

        $this->assertEquals($modelHasRoles1, $modelHasRoles2);

        $user = User::where('email', 'demo@perkly.test')->first();
        $this->assertNotNull($user);

        $org1 = Organization::where('slug', 'demo-org-1')->first();
        $ownerRole = DB::table('model_has_roles')
            ->where('model_id', $user->id)
            ->where('model_type', User::class)
            ->where('team_id', $org1->id)
            ->exists();
        $this->assertTrue($ownerRole);
    }
}