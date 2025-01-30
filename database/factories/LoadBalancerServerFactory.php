<?php

namespace Database\Factories;

use App\Models\LoadBalancerServer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class LoadBalancerServerFactory extends Factory
{
    protected $model = LoadBalancerServer::class;

    public function definition(): array
    {
        return [
            'load_balancer_id' => $this->faker->randomNumber(), //
            'ip' => $this->faker->ipv4(),
            'port' => $this->faker->randomNumber(),
            'weight' => $this->faker->randomNumber(),
            'backup' => $this->faker->boolean(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
