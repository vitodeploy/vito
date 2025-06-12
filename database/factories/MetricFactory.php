<?php

namespace Database\Factories;

use App\Models\Metric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Metric>
 */
class MetricFactory extends Factory
{
    public function definition(): array
    {
        return [
            'server_id' => 1,
            'load' => $this->faker->randomFloat(2, 0, 100),
            'memory_total' => $this->faker->randomFloat(0, 0, 100),
            'memory_used' => $this->faker->randomFloat(0, 0, 100),
            'memory_free' => $this->faker->randomFloat(0, 0, 100),
            'disk_total' => $this->faker->randomFloat(0, 0, 100),
            'disk_used' => $this->faker->randomFloat(0, 0, 100),
            'disk_free' => $this->faker->randomFloat(0, 0, 100),
        ];
    }
}
