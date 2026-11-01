<?php

namespace Database\Factories;

use App\Models\DeviceInstallation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceInstallationFactory extends Factory
{
    protected $model = DeviceInstallation::class;

    public function definition(): array
    {
        return [
            'id' => \Illuminate\Support\Str::ulid(),
            'user_id' => User::factory(),
            'platform' => 'android',
            'push_token' => 'token-' . \Illuminate\Support\Str::random(10),
            'app_version' => '1.0.0',
            'locale' => 'mk',
            'last_seen_at' => now(),
            'revoked_at' => null,
            'created_at' => now(),
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
        ]);
    }

    public function ios(): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => 'ios',
        ]);
    }
}