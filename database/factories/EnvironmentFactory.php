<?php

namespace Database\Factories;

use App\Enums\EnvironmentStatus;
use App\Models\Application;
use App\Models\Environment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Environment>
 */
class EnvironmentFactory extends Factory
{
    protected $model = Environment::class;

    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'name' => $this->faker->unique()->slug(2),
            'slug' => fn (array $attributes): string => $attributes['name'],
            'branch' => 'main',
            'runtime_version' => null,
            'primary_component_id' => null,
            'status' => EnvironmentStatus::READY,
        ];
    }
}
