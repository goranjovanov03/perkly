<?php

namespace Database\Factories;

use App\Models\File;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FileFactory extends Factory
{
    protected $model = File::class;

    public function definition(): array
    {
        return [
            'id' => \Illuminate\Support\Str::ulid(),
            'organization_id' => Organization::factory(),
            'uploader_id' => User::factory(),
            'original_name' => fake()->word() . '.pdf',
            'stored_path' => 'files/' . \Illuminate\Support\Str::ulid() . '.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => fake()->numberBetween(1000, 10000000),
            'sha256_hash' => hash('sha256', \Illuminate\Support\Str::random(32)),
            'created_at' => now(),
        ];
    }
}