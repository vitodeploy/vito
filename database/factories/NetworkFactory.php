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
        $cidr = $this->block();

        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->unique()->word(),
            'type' => NetworkType::WIREGUARD,
            'status' => NetworkStatus::ACTIVE,
            'addressing_pool' => NetworkAddressingPool::CGNAT,
            'cidr' => $cidr,
            'cidr_canonical' => $cidr,
            'port' => 51820,
        ];
    }

    /**
     * One unique number feeds both variable octets, so the pool spans every /24 in
     * 100.64.0.0/10 rather than the 256 a single-octet unique() would allow.
     */
    private function block(): string
    {
        $block = $this->faker->unique()->numberBetween(0, 16383);

        return '100.'.(64 + intdiv($block, 256)).'.'.($block % 256).'.0/24';
    }
}
