<?php

namespace Database\Factories;

use App\Models\Environment;
use App\Models\EnvironmentVariable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EnvironmentVariable>
 */
class EnvironmentVariableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'environment_id' => Environment::factory(),
            'key' => strtoupper($this->faker->unique()->word()),
            'value' => $this->faker->sentence(),
            'is_secret' => false,
        ];
    }
}
