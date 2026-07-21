<?php

namespace Database\Factories;

use App\Enums\NetworkAddressingPool;
use App\Enums\NetworkStatus;
use App\Enums\NetworkType;
use App\Models\Network;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Network>
 */
class NetworkFactory extends Factory
{
    protected $model = Network::class;

    public function definition(): array
    {
        $block = $this->faker->unique()->numberBetween(0, 255);

        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->unique()->word(),
            'type' => NetworkType::WIREGUARD,
            'status' => NetworkStatus::ACTIVE,
            'addressing_pool' => NetworkAddressingPool::CGNAT,
            'cidr' => "100.64.{$block}.0/24",
            'cidr_canonical' => "100.64.{$block}.0/24",
            'port' => 51820,
        ];
    }
}
