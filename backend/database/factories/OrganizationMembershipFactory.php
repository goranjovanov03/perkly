<?php

namespace Database\Factories;

use App\Models\OrganizationMembership;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrganizationMembership>
 */
class OrganizationMembershipFactory extends Factory
{
    protected $model = OrganizationMembership::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'user_id' => \App\Models\User::factory(),
            'organization_id' => \App\Models\Organization::factory(),
            'status' => $this->faker->randomElement(['active', 'inactive', 'pending']),
        ];
    }
}
