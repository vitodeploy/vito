<?php

namespace Database\Factories;

use App\Enums\FirewallRuleStatus;
use App\Models\Network;
use App\Models\NetworkFirewallRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NetworkFirewallRule>
 */
class NetworkFirewallRuleFactory extends Factory
{
    protected $model = NetworkFirewallRule::class;

    public function definition(): array
    {
        return [
            'network_id' => Network::factory(),
            'name' => $this->faker->word(),
            'protocol' => 'tcp',
            'port' => (string) $this->faker->numberBetween(1, 65535),
            'status' => FirewallRuleStatus::READY,
        ];
    }
}
