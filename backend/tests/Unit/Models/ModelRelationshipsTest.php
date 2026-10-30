<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\Person;
use App\Models\OrganizationMembership;
use App\Models\Assignment;
use App\Models\User;

class ModelRelationshipsTest extends TestCase
{
    public function test_organization_has_many_units(): void
    {
        $organization = Organization::factory()->create();
        $units = OrganizationUnit::factory()->count(3)->create(['organization_id' => $organization->id]);

        $this->assertEquals(3, $organization->units()->count());
        $this->assertInstanceOf(OrganizationUnit::class, $organization->units()->first());
    }

    public function test_organization_has_many_memberships(): void
    {
        $organization = Organization::factory()->create();
        $memberships = OrganizationMembership::factory()->count(2)->create(['organization_id' => $organization->id]);

        $this->assertEquals(2, $organization->memberships()->count());
        $this->assertInstanceOf(OrganizationMembership::class, $organization->memberships()->first());
    }

    public function test_organization_unit_belongs_to_organization(): void
    {
        $organization = Organization::factory()->create();
        $unit = OrganizationUnit::factory()->create(['organization_id' => $organization->id]);

        $this->assertInstanceOf(Organization::class, $unit->organization);
        $this->assertEquals($organization->id, $unit->organization->id);
    }

    public function test_organization_unit_has_parent_and_children(): void
    {
        $parent = OrganizationUnit::factory()->create();
        $child = OrganizationUnit::factory()->create(['parent_id' => $parent->id]);

        $this->assertInstanceOf(OrganizationUnit::class, $child->parent);
        $this->assertEquals($parent->id, $child->parent->id);

        $this->assertInstanceOf(OrganizationUnit::class, $parent->children()->first());
        $this->assertEquals($child->id, $parent->children()->first()->id);
    }

    public function test_organization_unit_has_many_assignments(): void
    {
        $unit = OrganizationUnit::factory()->create();
        $assignments = Assignment::factory()->count(2)->create(['organization_unit_id' => $unit->id]);

        $this->assertEquals(2, $unit->assignments()->count());
        $this->assertInstanceOf(Assignment::class, $unit->assignments()->first());
    }

    public function test_person_has_many_assignments(): void
    {
        $person = Person::factory()->create();
        $assignments = Assignment::factory()->count(3)->create(['person_id' => $person->id]);

        $this->assertEquals(3, $person->assignments()->count());
        $this->assertInstanceOf(Assignment::class, $person->assignments()->first());
    }

    public function test_person_has_one_user(): void
    {
        $person = Person::factory()->create();
        $user = User::factory()->create(['person_id' => $person->id]);

        $this->assertInstanceOf(User::class, $person->user);
        $this->assertEquals($user->id, $person->user->id);
    }

    public function test_organization_membership_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $membership = OrganizationMembership::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $membership->user);
        $this->assertEquals($user->id, $membership->user->id);
    }

    public function test_organization_membership_belongs_to_organization(): void
    {
        $organization = Organization::factory()->create();
        $membership = OrganizationMembership::factory()->create(['organization_id' => $organization->id]);

        $this->assertInstanceOf(Organization::class, $membership->organization);
        $this->assertEquals($organization->id, $membership->organization->id);
    }

    public function test_assignment_belongs_to_person(): void
    {
        $person = Person::factory()->create();
        $assignment = Assignment::factory()->create(['person_id' => $person->id]);

        $this->assertInstanceOf(Person::class, $assignment->person);
        $this->assertEquals($person->id, $assignment->person->id);
    }

    public function test_assignment_belongs_to_organization_unit(): void
    {
        $unit = OrganizationUnit::factory()->create();
        $assignment = Assignment::factory()->create(['organization_unit_id' => $unit->id]);

        $this->assertInstanceOf(OrganizationUnit::class, $assignment->organizationUnit);
        $this->assertEquals($unit->id, $assignment->organizationUnit->id);
    }

    public function test_user_belongs_to_person(): void
    {
        $person = Person::factory()->create();
        $user = User::factory()->create(['person_id' => $person->id]);

        $this->assertInstanceOf(Person::class, $user->person);
        $this->assertEquals($person->id, $user->person->id);
    }

    public function test_user_has_many_memberships(): void
    {
        $user = User::factory()->create();
        $memberships = OrganizationMembership::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertEquals(2, $user->memberships()->count());
        $this->assertInstanceOf(OrganizationMembership::class, $user->memberships()->first());
    }

    public function test_ulid_ids_are_strings(): void
    {
        $organization = Organization::factory()->create();
        $unit = OrganizationUnit::factory()->create();
        $person = Person::factory()->create();
        $membership = OrganizationMembership::factory()->create();
        $assignment = Assignment::factory()->create();

        $this->assertIsString($organization->id);
        $this->assertIsString($unit->id);
        $this->assertIsString($person->id);
        $this->assertIsString($membership->id);
        $this->assertIsString($assignment->id);
    }
}