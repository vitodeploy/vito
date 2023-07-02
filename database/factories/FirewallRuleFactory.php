<?php

namespace Database\Factories;

use App\Models\FirewallRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class FirewallRuleFactory extends Factory
{
    protected $model = FirewallRule::class;

    public function definition(): array
    {
        return [
            'type' => 'allow',
            'protocol' => 'tcp',
            'port' => $this->faker->numberBetween(1, 65535),
            'source' => $this->faker->ipv4(),
            'mask' => 24,
            'note' => 'test',
        ];
    }
}
