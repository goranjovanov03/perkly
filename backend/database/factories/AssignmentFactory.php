<?php

namespace Database\Factories;

use App\Models\Assignment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::ulid(),
            'person_id' => \App\Models\Person::factory(),
            'organization_unit_id' => \App\Models\OrganizationUnit::factory(),
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->optional()->date(),
            'role' => $this->faker->jobTitle(),
        ];
    }
}
