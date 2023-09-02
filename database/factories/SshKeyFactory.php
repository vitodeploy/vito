<?php

namespace Database\Factories;

use App\Models\SshKey;
use Illuminate\Database\Eloquent\Factories\Factory;

class SshKeyFactory extends Factory
{
    protected $model = SshKey::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'public_key' => 'public-key-content',
        ];
    }
}
