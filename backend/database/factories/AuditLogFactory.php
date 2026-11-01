<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'id' => \Illuminate\Support\Str::ulid(),
            'actor_id' => null,
            'organization_id' => null,
            'action' => fake()->word(),
            'resource_type' => fake()->optional()->word(),
            'resource_id' => fake()->optional()->uuid(),
            'metadata' => [],
            'request_id' => fake()->uuid(),
            'created_at' => now(),
        ];
    }
}