<?php

namespace Database\Factories;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'organization_id' => Organization::factory(),
            'channel' => 'in_app',
            'type' => 'notification.test',
            'enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'enabled' => false,
        ]);
    }

    public function push(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'push',
        ]);
    }

    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'channel' => 'email',
        ]);
    }
}