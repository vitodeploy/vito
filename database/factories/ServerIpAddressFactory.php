<?php

namespace Database\Factories;

use App\Enums\IpAddressFamily;
use App\Enums\IpAddressStatus;
use App\Enums\IpAddressType;
use App\Models\Server;
use App\Models\ServerIpAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServerIpAddress>
 */
class ServerIpAddressFactory extends Factory
{
    protected $model = ServerIpAddress::class;

    public function definition(): array
    {
        return [
            'server_id' => Server::factory(),
            'ip' => $this->faker->ipv4(),
            'prefix_length' => 32,
            'family' => IpAddressFamily::V4,
            'interface' => 'eth0',
            'type' => IpAddressType::PUBLIC,
            'status' => IpAddressStatus::CONFIGURED,
            'is_managed' => true,
            'is_primary' => false,
            'is_dynamic' => false,
        ];
    }
}
