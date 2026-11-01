<?php

namespace Database\Factories;

use App\Models\IntegrationConnection;
use App\Models\Organization;
use App\Models\IntegrationProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class IntegrationConnectionFactory extends Factory
{
    protected $model = IntegrationConnection::class;

    public function definition(): array
    {
        return [
            'id' => \Illuminate\Support\Str::ulid(),
            'organization_id' => Organization::factory(),
            'provider_key' => IntegrationProvider::inRandomOrder()->first()->key ?? 'stripe',
            'name' => 'Test Connection',
            'status' => 'active',
            'last_health_status' => null,
            'last_health_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}