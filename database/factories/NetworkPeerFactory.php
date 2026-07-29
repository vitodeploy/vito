<?php

namespace Database\Factories;

use App\Enums\NetworkPeerStatus;
use App\Models\Network;
use App\Models\NetworkPeer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NetworkPeer>
 */
class NetworkPeerFactory extends Factory
{
    protected $model = NetworkPeer::class;

    public function definition(): array
    {
        return [
            'network_id' => Network::factory(),
            'name' => $this->faker->unique()->word(),
            'ip' => $this->faker->unique()->numerify('100.64.0.##'),
            'public_key' => base64_encode(random_bytes(32)),
            'private_key' => base64_encode(random_bytes(32)),
            'byo' => false,
            'status' => NetworkPeerStatus::ACTIVE,
            'last_handshake_at' => null,
            'sync_attempts' => 0,
        ];
    }
}
