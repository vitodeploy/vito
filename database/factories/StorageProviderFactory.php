<?php

namespace Database\Factories;

use App\Models\StorageProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StorageProvider>
 */
class StorageProviderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'profile' => $this->faker->word(),
            'provider' => $this->faker->randomElement(array_keys(config('storage-provider.providers'))),
            'credentials' => [
                'token' => 'test-token',
            ],
            'user_id' => User::factory(),
        ];
    }
}
