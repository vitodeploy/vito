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
            'cpu_cores' => $this->faker->numberBetween(1, 32),
            'cpu_physical_cores' => $this->faker->numberBetween(1, 16),
            'cpu_usage_percent' => $this->faker->randomFloat(2, 0, 100),
            'cpu_per_core_usage_percent' => [
                $this->faker->randomFloat(2, 0, 100),
                $this->faker->randomFloat(2, 0, 100),
            ],
            'cpu_steal_percent' => $this->faker->randomFloat(2, 0, 100),
            'swap_total' => $this->faker->randomFloat(0, 0, 100),
            'swap_used' => $this->faker->randomFloat(0, 0, 100),
            'swap_free' => $this->faker->randomFloat(0, 0, 100),
            'swap_used_percent' => $this->faker->randomFloat(2, 0, 100),
            'oom_kill_count' => $this->faker->numberBetween(0, 5),
            'uptime_seconds' => $this->faker->randomFloat(2, 0, 1000000),
            'reboot_required' => false,
        ];
    }
}
