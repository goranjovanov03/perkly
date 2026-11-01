<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'id' => \Illuminate\Support\Str::ulid(),
            'organization_id' => Organization::factory(),
            'user_id' => User::factory(),
            'type' => 'notification.test',
            'title_key' => 'notification.test.title',
            'title_params' => ['name' => 'John'],
            'body_key' => 'notification.test.body',
            'body_params' => ['name' => 'John'],
            'data' => ['target_type' => 'test', 'target_id' => 'target-123'],
            'read_at' => null,
            'created_at' => now(),
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }
}