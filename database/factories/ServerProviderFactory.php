<?php

namespace Database\Factories;

use App\Models\ServerProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServerProviderFactory extends Factory
{
    protected $model = ServerProvider::class;

    public function definition(): array
    {
        return [
            'profile' => $this->faker->word(),
            'provider' => $this->faker->randomElement(config('core.server_providers')),
            'credentials' => [],
            'connected' => 1,
            'user_id' => User::factory(),
        ];
    }
}
