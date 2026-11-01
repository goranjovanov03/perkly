<?php

namespace Database\Factories;

use App\Models\OrganizationUnit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrganizationUnit>
 */
class OrganizationUnitFactory extends Factory
{
    protected $model = OrganizationUnit::class;

    public function definition(): array
    {
        $name = $this->faker->word();
        return [
            'id' => (string) Str::ulid(),
            'organization_id' => \App\Models\Organization::factory(),
            'parent_id' => null,
            'name' => $name,
            'slug' => Str::slug($name) . '-' . Str::random(6),
        ];
    }
}
