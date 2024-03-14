<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StorageProviderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'profile' => $this->faker->word(),
            'provider' => $this->faker->randomElement(\App\Enums\StorageProvider::getValues()),
            'credentials' => [
                'token' => 'test-token',
            ],
            'user_id' => User::factory(),
        ];
    }
}
