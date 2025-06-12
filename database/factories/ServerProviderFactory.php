<?php

namespace Database\Factories;

use App\Models\ServerProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServerProvider>
 */
class ServerProviderFactory extends Factory
{
    protected $model = ServerProvider::class;

    public function definition(): array
    {
        return [
            'profile' => $this->faker->word(),
            'provider' => $this->faker->randomElement(array_keys(config('server-provider.providers'))),
            'credentials' => [],
            'connected' => 1,
            'user_id' => User::factory(),
        ];
    }
}
