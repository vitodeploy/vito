<?php

namespace Database\Factories;

use App\Enums\NetworkServerStatus;
use App\Models\Network;
use App\Models\NetworkServer;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NetworkServer>
 */
class NetworkServerFactory extends Factory
{
    protected $model = NetworkServer::class;

    public function definition(): array
    {
        return [
            'network_id' => Network::factory(),
            'server_id' => Server::factory(),
            'server_ip_address_id' => null,
            'ip' => $this->faker->unique()->numerify('100.64.0.##'),
            'public_key' => base64_encode(random_bytes(32)),
            'private_key' => base64_encode(random_bytes(32)),
            'status' => NetworkServerStatus::ACTIVE,
            'sync_attempts' => 0,
        ];
    }
}
